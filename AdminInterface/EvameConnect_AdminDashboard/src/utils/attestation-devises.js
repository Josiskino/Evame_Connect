// Liste des devises proposées dans l'attestation d'importation.
// Portée à l'identique du document HTML d'origine (Studio Pro).
export const DEVISES = [
  { code: 'XOF', label: 'XOF - Franc CFA (BCEAO)' },
  { code: 'XAF', label: 'XAF - Franc CFA (BEAC)' },
  { code: 'EUR', label: 'EUR - Euro' },
  { code: 'USD', label: 'USD - Dollar US' },
  { code: 'GBP', label: 'GBP - Livre Sterling' },
  { code: 'CNY', label: 'CNY - Yuan Chinois' },
  { code: 'JPY', label: 'JPY - Yen Japonais' },
  { code: 'GHS', label: 'GHS - Cedi Ghanéen' },
  { code: 'NGN', label: 'NGN - Naira Nigérian' },
  { code: 'MAD', label: 'MAD - Dirham Marocain' },
  { code: 'TND', label: 'TND - Dinar Tunisien' },
  { code: 'ZAR', label: 'ZAR - Rand Sud-Africain' },
  { code: 'CAD', label: 'CAD - Dollar Canadien' },
  { code: 'CHF', label: 'CHF - Franc Suisse' },
  { code: 'INR', label: 'INR - Roupie Indienne' },
  { code: 'BRL', label: 'BRL - Real Brésilien' },
  { code: 'AED', label: 'AED - Dirham Émirats' },
  { code: 'SAR', label: 'SAR - Riyal Saoudien' },
  { code: 'TRY', label: 'TRY - Livre Turque' },
]

// Options { title, value } prêtes pour AppSelect / VSelect.
export const deviseSelectItems = (short = true) =>
  DEVISES.map(d => ({ title: short ? d.code : d.label, value: d.code }))
