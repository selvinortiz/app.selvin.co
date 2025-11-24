# Strategic Development Plan: Contractor & Contractor Invoice Tracking

## Overview
This plan outlines the implementation of contractor and contractor invoice tracking functionality to complement the existing client, hours, and invoice tracking system. This will enable tracking of outgoing expenses (contractor invoices) alongside incoming revenue (client invoices).

---

## Database Schema Design

### 1. Contractors Table
**Purpose**: Store contractor/vendor information for payment processing and tax reporting.

**Fields**:
- `id` (bigint, primary key)
- `tenant_id` (bigint, foreign key → tenants)
- `user_id` (bigint, foreign key → users)
- `name` (string, required) - Contractor's full name
- `business_name` (string, nullable) - Business name if different from personal name
- `email` (string, nullable) - Contact email
- `phone` (string, nullable) - Contact phone
- `address` (text, nullable) - Mailing address (for 1099 forms)
- `tax_id` (string, nullable) - EIN or SSN (for tax reporting)
- `payment_method` (string, nullable) - e.g., "ACH", "Check", "Wire", "PayPal"
- `bank_routing` (string, nullable) - Bank routing number (encrypted)
- `bank_account` (string, nullable) - Bank account number (encrypted)
- `notes` (text, nullable) - Internal notes
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Relationships**:
- `belongsTo`: Tenant, User
- `hasMany`: ContractorInvoice

**Indexes**:
- `tenant_id`, `user_id` for filtering
- `email` for searching

---

### 2. Contractor Invoices Table
**Purpose**: Track invoices received from contractors that need to be paid.

**Fields**:
- `id` (bigint, primary key)
- `tenant_id` (bigint, foreign key → tenants)
- `user_id` (bigint, foreign key → users)
- `contractor_id` (bigint, foreign key → contractors)
- `invoice_number` (string, required) - Invoice number from contractor
- `date` (date, required) - Invoice date
- `due_date` (date, required) - Payment due date
- `amount` (decimal 10,2, required) - Invoice amount
- `paid_at` (timestamp, nullable) - When payment was made (null = unpaid)
- `pdf_path` (string, nullable) - Path to stored PDF file
- `notes` (text, nullable) - Internal notes
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Relationships**:
- `belongsTo`: Contractor, Tenant, User

**Indexes**:
- `tenant_id`, `user_id` for filtering
- `contractor_id` for contractor relationship
- `invoice_number` for searching
- `date`, `due_date` for date-based queries
- `paid_at` for payment status filtering

**Computed Properties**:
- `is_paid` (boolean) - Derived from `paid_at !== null`
- `is_overdue` (boolean) - Derived from `paid_at === null && due_date < today`

---

## Implementation Phases

### Phase 1: Database & Models (Foundation)
**Estimated Time**: 1-2 hours

**Tasks**:
1. Create migration for `contractors` table
2. Create migration for `contractor_invoices` table
3. Create `Contractor` model with relationships and casts
4. Create `ContractorInvoice` model with relationships and casts
5. Update `Tenant` model to include contractor relationships
6. Add `HasUser` trait to both models
7. Create model factories for testing/seeding

**Deliverables**:
- `database/migrations/YYYY_MM_DD_create_contractors_table.php`
- `database/migrations/YYYY_MM_DD_create_contractor_invoices_table.php`
- `app/Models/Contractor.php`
- `app/Models/ContractorInvoice.php`
- `database/factories/ContractorFactory.php`
- `database/factories/ContractorInvoiceFactory.php`

---

### Phase 2: Filament Resources - Contractors (CRUD)
**Estimated Time**: 2-3 hours

**Tasks**:
1. Create `ContractorResource` with form, table, and pages
2. Implement form fields:
   - Business Information section (name, business_name, email, phone, address)
   - Tax Information section (tax_id)
   - Payment Information section (payment_method, bank_routing, bank_account)
   - Notes section
3. Implement table columns (name, email, phone, tax_id, created_at)
4. Add search and filters
5. Create List, Create, Edit pages
6. Add navigation item (separate section, sort order 30)

**Deliverables**:
- `app/Filament/Resources/ContractorResource.php`
- `app/Filament/Resources/ContractorResource/Pages/ListContractors.php`
- `app/Filament/Resources/ContractorResource/Pages/CreateContractor.php`
- `app/Filament/Resources/ContractorResource/Pages/EditContractor.php`

**Security Considerations**:
- Encrypt `bank_routing` and `bank_account` fields (use Laravel encryption)
- Only display last 4 digits in UI
- Add access control if needed

---

### Phase 3: Filament Resources - Contractor Invoices (CRUD)
**Estimated Time**: 3-4 hours

**Tasks**:
1. Create `ContractorInvoiceResource` with form, table, and pages
2. Implement form fields:
   - Contractor selection (required, searchable)
   - Auto-populate contractor info in read-only section
   - Invoice details (invoice_number, date, due_date, amount)
   - Payment tracking (paid_at date picker)
   - PDF upload field (FileUpload component)
   - Notes field
3. Implement table columns:
   - Invoice number (link to view)
   - Contractor name
   - Date, Due Date
   - Amount
   - Status badge (Paid/Unpaid, with overdue indicator)
4. Add filters:
   - By contractor
   - By payment status (paid/unpaid)
   - By overdue status
   - By date range
5. Add actions:
   - Mark as paid
   - View PDF (if exists)
   - Download PDF
6. Create List, Create, Edit pages
7. Add navigation item (sort order 31, under Contractors section)

**Deliverables**:
- `app/Filament/Resources/ContractorInvoiceResource.php`
- `app/Filament/Resources/ContractorInvoiceResource/Pages/ListContractorInvoices.php`
- `app/Filament/Resources/ContractorInvoiceResource/Pages/CreateContractorInvoice.php`
- `app/Filament/Resources/ContractorInvoiceResource/Pages/EditContractorInvoice.php`

**File Storage**:
- Store PDFs in `storage/app/private/contractor-invoices/`
- Use Laravel's filesystem with private disk
- Generate unique filenames: `{contractor_id}_{invoice_number}_{timestamp}.pdf`

---

### Phase 4: Dashboard Widget
**Estimated Time**: 1-2 hours

**Tasks**:
1. Create `ContractorInvoiceSummary` widget
2. Display stats:
   - Total Unpaid (count and amount)
   - Overdue Invoices (count and amount)
   - Total Paid This Month (amount)
3. Integrate with `MonthContextService` for month filtering
4. Add to dashboard widget list
5. Style consistently with existing `InvoiceStatusSummary` widget

**Deliverables**:
- `app/Filament/Widgets/ContractorInvoiceSummary.php`
- Update `app/Filament/Pages/Dashboard.php` to include widget

**Widget Stats**:
- **Unpaid Invoices**: Count and total amount of unpaid invoices
- **Overdue Invoices**: Count and total amount of unpaid invoices past due date (red/warning color)
- **Paid This Month**: Total amount paid in selected month (green/success color)

---

### Phase 5: Testing & Refinement
**Estimated Time**: 1-2 hours

**Tasks**:
1. Test contractor CRUD operations
2. Test contractor invoice CRUD operations
3. Test PDF upload and retrieval
4. Test payment tracking (marking as paid)
5. Test widget calculations
6. Test month context filtering
7. Verify navigation and UI consistency
8. Test with multiple tenants (if applicable)
9. Add validation rules
10. Test edge cases (duplicate invoice numbers, etc.)

**Deliverables**:
- Functional testing complete
- UI/UX refinements
- Bug fixes

---

## File Structure

```
app/
├── Models/
│   ├── Contractor.php (NEW)
│   ├── ContractorInvoice.php (NEW)
│   └── Tenant.php (UPDATE - add relationships)
│
├── Filament/
│   ├── Resources/
│   │   ├── ContractorResource.php (NEW)
│   │   │   └── Pages/
│   │   │       ├── ListContractors.php (NEW)
│   │   │       ├── CreateContractor.php (NEW)
│   │   │       └── EditContractor.php (NEW)
│   │   │
│   │   └── ContractorInvoiceResource.php (NEW)
│   │       └── Pages/
│   │           ├── ListContractorInvoices.php (NEW)
│   │           ├── CreateContractorInvoice.php (NEW)
│   │           └── EditContractorInvoice.php (NEW)
│   │
│   ├── Widgets/
│   │   └── ContractorInvoiceSummary.php (NEW)
│   │
│   └── Pages/
│       └── Dashboard.php (UPDATE - add widget)
│
database/
├── migrations/
│   ├── YYYY_MM_DD_create_contractors_table.php (NEW)
│   └── YYYY_MM_DD_create_contractor_invoices_table.php (NEW)
│
└── factories/
    ├── ContractorFactory.php (NEW)
    └── ContractorInvoiceFactory.php (NEW)
```

---

## Key Features & Design Decisions

### 1. Naming Convention
- **"Contractor"** terminology (as requested, can be renamed to "Vendor" later if needed)
- Separate navigation section for clarity

### 2. Simplicity First
- Contractor invoices are simpler than client invoices (no status enum, just paid_at timestamp)
- No project/client linking yet (can be added in future)
- Minimal fields, maximum utility

### 3. Security
- Encrypt sensitive bank information
- Store PDFs in private storage
- Use Laravel's built-in file handling

### 4. User Experience
- Consistent with existing invoice UI patterns
- Clear visual indicators for paid/unpaid/overdue
- Easy PDF upload and viewing
- Quick payment tracking (mark as paid with date)

### 5. Reporting
- Simple widget showing key metrics
- Month context integration (consistent with existing widgets)
- No complex analytics yet (can be added later)

### 6. Extensibility
- Schema allows for future enhancements:
  - Project/client linking
  - Expense categorization
  - Payment method tracking
  - Recurring invoices
  - Approval workflows

---

## Technical Considerations

### Encryption
For bank account information, use Laravel's encryption:
```php
// In Contractor model
protected $casts = [
    'bank_routing' => 'encrypted',
    'bank_account' => 'encrypted',
];
```

### File Storage
- Use `Storage::disk('local')` for private PDF storage
- Path: `contractor-invoices/{contractor_id}/{filename}`
- Generate unique filenames to prevent conflicts
- Consider file size limits (default 10MB should be sufficient)

### Date Handling
- Use Carbon for date calculations
- Integrate with existing `MonthContextService` for consistency
- Handle timezone considerations

### Validation Rules
- Invoice number: required, unique per contractor (optional)
- Amount: required, numeric, min:0
- Dates: required, valid dates
- PDF: optional, max:10MB, mimes:pdf

---

## Future Enhancements (Out of Scope for Now)

1. **Project Linking**: Link contractor invoices to specific client projects
2. **Expense Categories**: Categorize expenses for tax purposes
3. **Payment Tracking**: Track actual payment transactions (check numbers, transaction IDs)
4. **Recurring Invoices**: Set up recurring contractor invoices
5. **1099 Generation**: Generate 1099 forms for contractors
6. **Approval Workflows**: Add approval process before payment
7. **Email Notifications**: Notify when invoices are due
8. **Reporting Dashboard**: More detailed expense vs. revenue analytics
9. **Bulk Operations**: Bulk mark as paid, bulk upload PDFs
10. **Contractor Portal**: Allow contractors to submit invoices directly

---

## Success Criteria

✅ Contractors can be created, edited, and managed
✅ Contractor invoices can be created with PDF upload
✅ Payment status can be tracked (paid_at timestamp)
✅ Overdue invoices are clearly identified
✅ Dashboard widget shows key metrics
✅ Month context filtering works correctly
✅ PDFs are securely stored and accessible
✅ UI is consistent with existing design patterns
✅ Navigation is clear and intuitive
✅ All data is properly scoped to user/tenant

---

## Estimated Total Time
**8-12 hours** of development time

---

## Next Steps

1. Review and approve this plan
2. Begin Phase 1 (Database & Models)
3. Iterate through phases sequentially
4. Test thoroughly before moving to next phase
5. Gather feedback and refine as needed

---

## Questions for Clarification

Before starting implementation, please confirm:

1. **Bank Information**: Should bank routing/account numbers be encrypted? (Recommended: Yes)
2. **Invoice Number Uniqueness**: Should invoice numbers be unique globally or per contractor? (Recommended: Per contractor, but not enforced)
3. **PDF Storage**: Is 10MB limit sufficient for PDF files?
4. **Payment Method**: Should we have a dropdown of common payment methods, or free text?
5. **Tax ID Format**: Should we validate EIN/SSN format, or allow free text?
6. **Default Payment Terms**: Should contractors have default payment terms (like clients do)?

---

*Document created: 2025-01-XX*
*Last updated: 2025-01-XX*
