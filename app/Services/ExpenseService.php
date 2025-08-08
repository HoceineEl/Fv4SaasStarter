<?php

namespace App\Services;

use App\Enums\Others\ExpenseType;
use App\Enums\Status;
use App\Models\Tenant;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ExpenseService
{
    protected ?Model $model = null;

    protected ?string $title = null;

    protected ?string $description = null;

    protected ?float $amount = null;

    protected ?float $taxAmount = null;

    protected ?string $expenseDate = null;

    protected ?int $categoryId = null;

    protected ?array $receiptData = null;

    protected ?int $vendorId = null;

    protected ?bool $isRecurring = false;

    protected ?string $recurringFrequency = null;

    protected ?string $recurringEndDate = null;

    protected ?array $properties = [];

    protected ?Status $status = Status::PENDING;

    protected ?string $rejectionReason = null;

    protected ?Model $approvedBy = null;

    protected ?int $parentId = null;

    protected ?PurchaseOrder $purchaseOrder = null;

    protected ?int $branchId = null;

    /**
     * Set the model that the expense is related to
     */
    public function for(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set the expense title
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the expense description
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the expense amount
     */
    public function amount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the tax amount
     */
    public function taxAmount(float | string | null $taxAmount): self
    {
        $this->taxAmount = is_string($taxAmount) ? (float) $taxAmount : $taxAmount;

        return $this;
    }

    /**
     * Set the parent expense ID
     */
    public function parent(int $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Set the expense date
     */
    public function date(string $date): self
    {
        $this->expenseDate = $date;

        return $this;
    }

    /**
     * Set the expense category ID
     */
    public function category(int | string | ExpenseCategory $category): self
    {
        $this->categoryId = $category instanceof ExpenseCategory ? $category->id : $category;

        return $this;
    }

    public function categoryByName(string $categoryName, ?Tenant $tenant = null, ?ExpenseType $type = ExpenseType::OTHER): self
    {
        $tenantId = $tenant?->id ?? tenant('id');

        $category = ExpenseCategory::query()
            ->withoutGlobalScopes()
            ->firstOrCreate(
                ['name' => $categoryName, 'tenant_id' => $tenantId],
                ['tenant_id' => $tenantId, 'type' => $type]
            );

        $this->categoryId = $category->id;

        return $this;
    }

    /**
     * Set the receipt data
     */
    public function receipt(?array $receiptData): self
    {
        $this->receiptData = $receiptData;

        return $this;
    }

    /**
     * Set the vendor ID
     */
    public function vendor(?int $vendorId): self
    {
        $this->vendorId = $vendorId;

        return $this;
    }

    /**
     * Set the expense as recurring
     */
    public function recurring(bool $isRecurring = true, ?string $frequency = null, ?string $endDate = null): self
    {
        $this->isRecurring = $isRecurring;
        $this->recurringFrequency = $frequency;
        $this->recurringEndDate = $endDate;

        return $this;
    }

    /**
     * Set custom properties for the expense
     */
    public function withProperties(array $properties): self
    {
        $this->properties = array_merge($this->properties ?? [], $properties);

        return $this;
    }

    public function status(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set the expense as approved
     */
    public function approve(): self
    {
        $this->status = Status::APPROVED;
        $this->approvedBy = Auth::user();

        return $this;
    }

    /**
     * Set the expense as rejected
     */
    public function reject(string $reason): self
    {
        $this->status = Status::REJECTED;
        $this->rejectionReason = $reason;
        $this->approvedBy = Auth::user();

        return $this;
    }

    public function purchaseOrder(PurchaseOrder $purchaseOrder): self
    {
        $this->purchaseOrder = $purchaseOrder;

        return $this;
    }

    public function branch(int | string | null | Branch $branch): self
    {
        $this->branchId = $branch instanceof Branch ? $branch->id : $branch;

        return $this;
    }

    /**
     * Create the expense record
     */
    public function create(bool $quitely = false, ?Tenant $tenant = null): ?Expense
    {
        if (! $this->title || ! $this->amount || ! $this->expenseDate) {
            return null;
        }

        $expense = new Expense;
        $expense->title = $this->title;
        $expense->description = $this->description;
        $expense->amount = $this->amount;
        $expense->expense_date = $this->expenseDate;
        $expense->expense_category_id = $this->categoryId;
        $expense->receipt_data = $this->receiptData;
        $expense->vendor_id = $this->vendorId;
        $expense->is_recurring = $this->isRecurring;
        $expense->recurring_frequency = $this->recurringFrequency;
        $expense->recurring_end_date = $this->recurringEndDate;
        $expense->parent_expense_id = $this->parentId;
        $expense->branch_id = $this->branchId;
        $expense->status = $this->status ?? Status::PENDING;
        $expense->rejection_reason = $this->rejectionReason;
        $expense->created_by = Auth::id() ?? null;
        $expense->purchase_order_id = $this->purchaseOrder?->id ?? null;

        // Set tax amount if provided
        if ($this->taxAmount !== null && (float) $this->taxAmount > 0) {
            $expense->setAttribute('tax_amount', $this->taxAmount);
        }

        // If a model is provided, associate it with the expense
        if ($this->model) {
            $expense->expenseable()->associate($this->model);
        }
        if ($quitely) {
            $expense->tenant_id = $tenant->id ?? tenant('id');
            $expense->saveQuietly();
        } else {
            $expense->tenant_id = $tenant?->id ?? tenant('id');
            $expense->save();
        }

        if (! $quitely) {
            // Log the expense creation
            activity()
                ->performedOn($expense)
                ->causedBy(Auth::user())
                ->withProperties(array_merge([
                    'user_id' => Auth::id(),
                    'expense_id' => $expense->id,
                    'icon' => 'tabler-receipt',
                ], $this->properties ?? []))
                ->log(__('expenses.logs.created', ['expense' => $expense->title]));
        }

        return $expense;
    }

    /**
     * Generate PDF for an expense receipt
     */
    public function generatePdf(Expense $expense, array $config = []): string
    {
        try {
            // Get logo if available
            $logo = $this->getTenantLogo($expense->tenant);

            // Prepare view data
            $data = [
                'expense' => $expense,
                'logo' => $logo,
                'currency' => $expense->tenant->settings->currency ?? 'SAR',
                'tenant' => $expense->tenant,
            ];

            // Configure mPDF with default or custom settings
            $mpdf = $this->configureMpdf($config);

            // Set PDF metadata
            $this->setPdfMetadata($mpdf, $expense);

            // Generate and return PDF content
            return $this->renderPdf($mpdf, $data);
        } catch (\Throwable $e) {
            $this->logError('generate_pdf', $e, ['config' => $config]);

            throw $e;
        }
    }

    /**
     * Download expense receipt as PDF
     */
    public function downloadPdf(Expense $expense, array $config = []): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $pdfContent = $this->generatePdf($expense, $config);

            return response()->streamDownload(
                function () use ($pdfContent) {
                    echo $pdfContent;
                },
                'expense_receipt_' . $expense->id . '_' . $expense->expense_date->format('Y-m-d') . '.pdf'
            );
        } catch (\Throwable $e) {
            $this->logError('download_pdf', $e, ['config' => $config]);

            throw $e;
        }
    }

    /**
     * Get tenant logo as base64
     */
    protected function getTenantLogo($tenant): ?string
    {
        try {
            $logoPath = storage_path('app/public/' . $tenant->logo_path);
            if (! file_exists($logoPath)) {
                return null;
            }

            $image = \Intervention\Image\Facades\Image::make($logoPath);

            if ($image->width() > 150 || $image->height() > 150) {
                $image->resize(150, 150, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            $width = $image->width();
            $height = $image->height();

            $mask = \Intervention\Image\Facades\Image::canvas($width, $height);
            $mask->circle($width, $width / 2, $height / 2, function ($draw) {
                $draw->background('#fff');
            });

            $image->mask($mask, false);

            $image->encode('jpg', 90);
            $image->encode('png', 90);

            $imageData = $image->encode('png')->encoded;
            if (function_exists('imagecreatefromstring')) {
                $im = imagecreatefromstring($imageData);
                if ($im !== false) {
                    imagesavealpha($im, true);
                    ob_start();
                    imagepng($im, null, 9, PNG_ALL_FILTERS);
                    $imageData = ob_get_clean();
                    imagedestroy($im);
                }
            }

            return 'data:image/png;base64,' . base64_encode($imageData);
        } catch (\Throwable $e) {
            $this->logError('get_tenant_logo', $e, ['tenant_id' => $tenant->id]);

            return null;
        }
    }

    /**
     * Configure mPDF instance
     */
    protected function configureMpdf(array $config = []): \Mpdf\Mpdf
    {
        $defaultConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'tempDir' => storage_path('app/pdf_fonts'),
            'fontDir' => [
                public_path('fonts'),
                public_path('pdf_fonts'),
            ],
            'fontdata' => [
                'dinnextltarabic' => [
                    'R' => 'dinnextltarabic_medium_normal_ab9f5a2326967c69e338559eaff07d99.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
                'saudiriyal' => [
                    'R' => 'saudi_riyal/saudi_riyal.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => 'dinnextltarabic',
            'fontCache' => true,
        ];

        // Merge custom config with defaults
        $finalConfig = array_merge($defaultConfig, $config);

        return new \Mpdf\Mpdf($finalConfig);
    }

    /**
     * Set PDF metadata
     */
    protected function setPdfMetadata(\Mpdf\Mpdf $mpdf, Expense $expense): void
    {
        $mpdf->SetTitle('Expense Receipt #' . $expense->id);
        $mpdf->SetAuthor($expense->tenant->name);
        $mpdf->SetCreator($expense->tenant->name);
        $mpdf->SetFont('dinnextltarabic', '', 10, forcewrite: true);
        $mpdf->SetDirectionality('rtl');

        // Register Saudi Riyal font for use in the PDF
        $mpdf->AddFontDirectory(public_path('fonts'));
        $mpdf->AddFont('SaudiRiyal', '');
    }

    /**
     * Render PDF content
     */
    protected function renderPdf(\Mpdf\Mpdf $mpdf, array $data): string
    {
        try {
            $data['logo'] = $this->getTenantLogo($data['tenant']);
            $html = view('expenses.expense-pdf', $data)->render();
            $mpdf->WriteHTML($html);

            return $mpdf->Output('', 'S');
        } catch (\Throwable $e) {
            $this->logError('render_pdf', $e, ['data' => array_keys($data)]);

            // Re-throw the exception after logging
            throw $e;
        }
    }

    /**
     * Log error with consistent format
     */
    protected function logError(string $action, \Throwable $e, array $context = []): void
    {
        $baseContext = [
            'error' => $e->getMessage(),
            'expense_id' => $this->expense->id ?? null,
            'trace' => $e->getTraceAsString(),
        ];

        \Illuminate\Support\Facades\Log::error("Expense error: {$action}", array_merge($baseContext, $context));
    }
}
