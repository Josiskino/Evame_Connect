/* eslint-disable camelcase -- les clés (pct_fob, valeur_douane…) reprennent le contrat backend / document officiel */
// Logique de calcul de l'attestation d'importation.
// Port fidèle (fonctions pures) du JS du document HTML d'origine afin de
// garantir une parité au franc près avec le fichier fourni par le client.

// Parse un montant saisi au format français (espaces = milliers, virgule = décimale).
export const parseNum = str => {
  if (!str)
    return 0

  return Math.round(
    parseFloat(String(str).replace(/\s/g, '').replace(/\./g, '').replace(/,/g, '.')) || 0,
  )
}

// Formate un nombre avec des espaces comme séparateurs de milliers.
export const fmtNum = n => {
  if (isNaN(n) || n === 0)
    return '0'

  const neg = n < 0
  const str = Math.abs(Math.round(n)).toString()
  let r = ''
  for (let i = str.length - 1, c = 0; i >= 0; i--, c++) {
    if (c > 0 && c % 3 === 0)
      r = ` ${r}`
    r = str[i] + r
  }

  return neg ? `-${r}` : r
}

// Reformate une saisie « valeur en douane » en n'autorisant que des chiffres,
// avec insertion automatique des espaces (équivalent de onCafInput()).
export const formatCafInput = value => {
  const raw = String(value ?? '').replace(/\D/g, '')
  if (raw === '')
    return ''

  return fmtNum(parseInt(raw, 10))
}

export const DEFAULT_SETTINGS = {
  pct_fob: 80,
  pct_fret: 15,
  pct_ass: 5,
  taux_change: 655.957,
}

// Calcule l'ensemble des valeurs dérivées (champs « en bleu » du document)
// à partir de l'état du formulaire et des paramètres. Retourne des chaînes
// déjà formatées, prêtes à l'affichage — exactement comme le HTML d'origine.
export const computeDerived = (form, settings) => {
  const s = { ...DEFAULT_SETTINGS, ...(settings || {}) }
  const taux = Number(s.taux_change) || DEFAULT_SETTINGS.taux_change

  // Valeur en douane = somme de la dernière colonne (FCFA) du tableau marchandises.
  const valeurDouane = (form.goods || []).reduce(
    (sum, row) => sum + parseNum(row?.valeur),
    0,
  )

  // Facture CAF (devise) convertie en FCFA.
  const cafFactureDevise = parseNum(form.facture_caf)
  const factureCafFcfa = cafFactureDevise ? Math.round(cafFactureDevise * taux) : 0

  // Décomposition de la valeur en douane (% paramétrables).
  const valeurFob = valeurDouane ? Math.round(valeurDouane * s.pct_fob / 100) : 0
  const fret = valeurDouane ? Math.round(valeurDouane * s.pct_fret / 100) : 0
  const assurance = valeurDouane ? Math.round(valeurDouane * s.pct_ass / 100) : 0

  // Ajustement = Facture CAF en FCFA − Valeur en douane.
  const ajustement = (factureCafFcfa && valeurDouane) ? factureCafFcfa - valeurDouane : null

  // FOB en devise = % de la facture CAF en devise.
  const factureFob = cafFactureDevise ? Math.round(cafFactureDevise * s.pct_fob / 100) : 0

  return {
    valeur_douane: valeurDouane ? fmtNum(valeurDouane) : '',
    facture_caf_fcfa: factureCafFcfa ? fmtNum(factureCafFcfa) : '',
    valeur_fob: valeurFob ? fmtNum(valeurFob) : '',
    fret: fret ? fmtNum(fret) : '',
    assurance: assurance ? fmtNum(assurance) : '',
    ajustement: ajustement !== null ? fmtNum(ajustement) : '',
    facture_fob: factureFob ? fmtNum(factureFob) : '',
  }
}

// Vrai si la répartition FOB/Fret/Assurance totalise bien 100 %.
export const settingsTotalIsValid = settings => {
  const total = (Number(settings?.pct_fob) || 0)
    + (Number(settings?.pct_fret) || 0)
    + (Number(settings?.pct_ass) || 0)

  return Math.abs(total - 100) < 0.01
}
