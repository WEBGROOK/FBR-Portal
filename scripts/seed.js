const { PrismaClient } = require('@prisma/client');
const bcrypt = require('bcryptjs');

const prisma = new PrismaClient();

async function main() {
  console.log('Seeding initial data...');

  const passwordHash = await bcrypt.hash('password123', 10);

  // 1. Create Default Shop Admin User
  const user = await prisma.user.upsert({
    where: { email: 'owner@almadina.pk' },
    update: {
      passwordHash,
      name: 'Al-Madina General Store Admin',
      posId: 'POS-100234',
      sellerNTN: '7890123-4',
    },
    create: {
      email: 'owner@almadina.pk',
      passwordHash,
      name: 'Al-Madina General Store Admin',
      role: 'ADMIN',
      posId: 'POS-100234',
      sellerNTN: '7890123-4',
    },
  });

  console.log('User created:', user.email);

  // 2. Create Initial Sample Invoice Batch for Immediate Preview
  const batch = await prisma.invoiceBatch.create({
    data: {
      userId: user.id,
      fileName: 'Daily_Sales_Batch_2026-08-25.xlsx',
      fileType: 'xlsx',
      fileSize: 45200,
      totalCount: 3,
      validCount: 2,
      invalidCount: 1,
      status: 'COMPLETED',
    },
  });

  // Invoice 1: VALID Ready for FBR
  const inv1 = await prisma.invoice.create({
    data: {
      batchId: batch.id,
      userId: user.id,
      invoiceNumber: 'INV-2026-0801',
      invoiceDate: new Date('2026-08-25T10:30:00Z'),
      sellerNTN: '7890123-4',
      sellerName: 'AL-MADINA TRADERS LTD',
      sellerPOSID: 'POS-100234',
      buyerNTN: '3456789-2',
      buyerName: 'National Logistics Corp',
      buyerCNIC: null,
      buyerPhone: '0300-1234567',
      paymentMode: '1',
      totalSaleValue: 15000.0,
      totalQuantity: 10,
      totalTaxAmount: 2700.0,
      discount: 0,
      furtherTax: 0,
      totalBill: 17700.0,
      validationStatus: 'VALID',
      fbrStatus: 'READY',
      items: {
        create: [
          {
            itemCode: 'SKU-PAPER-01',
            itemName: 'A4 Printing Paper Reams (Box of 5)',
            PCTCode: '4802.5600',
            quantity: 10,
            unitPrice: 1500.0,
            discount: 0,
            saleValue: 15000.0,
            taxRate: 18.0,
            taxCharged: 2700.0,
            totalAmount: 17700.0,
          },
        ],
      },
    },
  });

  // Invoice 2: ACCEPTED by FBR
  const inv2 = await prisma.invoice.create({
    data: {
      batchId: batch.id,
      userId: user.id,
      invoiceNumber: 'INV-2026-0802',
      invoiceDate: new Date('2026-08-25T11:15:00Z'),
      sellerNTN: '7890123-4',
      sellerName: 'AL-MADINA TRADERS LTD',
      sellerPOSID: 'POS-100234',
      buyerNTN: null,
      buyerName: 'Tariq Mehmood',
      buyerCNIC: '42101-1234567-1',
      buyerPhone: '0321-9876543',
      paymentMode: '2',
      totalSaleValue: 8500.0,
      totalQuantity: 2,
      totalTaxAmount: 1530.0,
      discount: 200.0,
      furtherTax: 0,
      totalBill: 9830.0,
      validationStatus: 'VALID',
      fbrStatus: 'ACCEPTED',
      fbrInvoiceNumber: 'FBR-100234-884920',
      items: {
        create: [
          {
            itemCode: 'SKU-ELEC-05',
            itemName: 'Wireless Industrial Scanner',
            PCTCode: '8471.6000',
            quantity: 2,
            unitPrice: 4250.0,
            discount: 200.0,
            saleValue: 8300.0,
            taxRate: 18.0,
            taxCharged: 1530.0,
            totalAmount: 9830.0,
          },
        ],
      },
    },
  });

  // Invoice 3: INVALID - Missing required field & tax math check
  const inv3 = await prisma.invoice.create({
    data: {
      batchId: batch.id,
      userId: user.id,
      invoiceNumber: 'INV-2026-0803',
      invoiceDate: new Date('2026-08-25T12:00:00Z'),
      sellerNTN: '7890123-4',
      sellerName: 'AL-MADINA TRADERS LTD',
      sellerPOSID: 'POS-100234',
      buyerNTN: 'INVALID-NTN-FORMAT',
      buyerName: '',
      buyerCNIC: null,
      buyerPhone: null,
      paymentMode: '1',
      totalSaleValue: 5000.0,
      totalQuantity: 0,
      totalTaxAmount: 0,
      discount: 0,
      furtherTax: 0,
      totalBill: 5000.0,
      validationStatus: 'MISSING_REQUIRED_FIELD',
      validationErrors: JSON.stringify([
        {
          field: 'buyerName',
          message: 'Buyer / Customer name is missing.',
          code: 'MISSING_FIELD',
        },
        {
          field: 'buyerNTN',
          message: 'Buyer NTN format should be 7 digits followed by check digit.',
          code: 'INVALID_FORMAT',
        },
      ]),
      fbrStatus: 'FAILED',
      lastError: 'Validation checks failed prior to FBR submission.',
      items: {
        create: [
          {
            itemCode: 'SKU-UNSET',
            itemName: 'Misc Unassigned Product',
            PCTCode: '9901.0000',
            quantity: 0,
            unitPrice: 5000.0,
            discount: 0,
            saleValue: 0,
            taxRate: 0,
            taxCharged: 0,
            totalAmount: 0,
          },
        ],
      },
    },
  });

  console.log('Sample invoices seeded successfully!');
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
