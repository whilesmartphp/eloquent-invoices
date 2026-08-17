## [1.2.0] - 2026-08-18

### Added
- Estimates: line-item documents with per-owner numbering that accept into a draft invoice, carrying their line items
- Cost breakdown behind an estimate that rolls up into margin without changing the customer-facing total
- Estimate endpoints: CRUD plus send, accept and decline, owner-scoped like invoices

## [1.1.0] - 2026-06-13

### Added
- Invoices can now be edited after creation: the update endpoint accepts line items, replaces them, and recalculates totals
- Editing is blocked for paid or void invoices, which are returned as a validation error

## [1.0.0] - 2026-06-10
- Polymorphic invoice management with line items, scoped per owner via owner-access
- Auto-generate per-workspace invoice numbers when none is supplied, unique per owner, with a configurable padding length
- Emit InvoiceSent, InvoicePaid, and InvoicePartiallyPaid events so host apps can deliver invoices and trigger automations
- Record payments through the payments package when marking an invoice paid, keeping totals and status in sync
