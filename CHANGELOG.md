## [1.0.0] - 2026-06-10
- Polymorphic invoice management with line items, scoped per owner via owner-access
- Auto-generate per-workspace invoice numbers when none is supplied, unique per owner
- Emit InvoiceSent and InvoicePaid events so host apps can deliver invoices and trigger automations
- Record payments through the payments package when marking an invoice paid, keeping totals and status in sync
