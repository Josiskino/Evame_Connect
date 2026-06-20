/* eslint-disable camelcase -- champs du document officiel alignés sur le contrat backend */
// Impression « 1 page A4 » de l'attestation d'importation.
//
// L'impression est totalement découplée de l'UI Vuetify : on génère un document
// HTML propre à partir des données (form + valeurs calculées), on l'injecte dans
// une iframe cachée puis on déclenche l'impression. Cette approche évite les
// pop-ups bloquées par le navigateur (window.open) et n'affiche aucune alerte
// native — les erreurs éventuelles sont remontées via le callback onError.

import { DEVISES } from '@/utils/attestation-devises'

const esc = value => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')

// yyyy-mm-dd → jj/mm/aaaa (laisse le reste tel quel).
const formatDate = value => {
  if (!value)
    return ''

  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value))

  return m ? `${m[3]}/${m[2]}/${m[1]}` : String(value)
}

const deviseLabel = code => DEVISES.find(d => d.code === code)?.label ?? code ?? ''

const PRINT_CSS = `
  @page { size: A4 portrait; margin: 0; }
  * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  html, body {
    width: 210mm; height: 297mm; margin: 0; padding: 0; overflow: hidden;
    background: #fff; color: #111; font-family: Arial, Helvetica, sans-serif;
  }
  #print-page {
    width: 210mm; height: 297mm; overflow: hidden; background: #fff;
    padding: 6mm 7mm; page-break-inside: avoid;
  }
  #scale-box { width: 196mm; transform-origin: top left; }
  .document { width: 196mm; color: #111; font-size: 8.4pt; line-height: 1.18; }
  .doc-title {
    text-align: center; font-size: 16pt; font-weight: 700; text-decoration: underline;
    text-underline-offset: 3px; letter-spacing: .6px; margin: 0 0 2.5mm;
  }
  .num-code { text-align: right; font-size: 9pt; margin-bottom: 2mm; }
  .num-code b { font-weight: 700; }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 7mm; }
  .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4mm; }
  .section-label {
    font-size: 8.6pt; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
    color: #111; border-bottom: 1px solid #111; padding-bottom: .6mm; margin: 3mm 0 1.6mm;
  }
  .field { margin-bottom: 1.1mm; }
  .field label { display: block; font-size: 6.6pt; font-weight: 700; color: #555; text-transform: uppercase; }
  .field .v { font-size: 8.6pt; min-height: 3.6mm; padding: .3mm 0; border-bottom: .2mm dotted #bbb; }
  .field .v.strong { font-weight: 700; }
  .sep { border: none; border-top: 1px solid #ccc; margin: 2.6mm 0; }
  table.grid { width: 100%; border-collapse: collapse; margin: 1.6mm 0; }
  table.grid th { background: #333; color: #fff; font-size: 6.7pt; font-weight: 700; text-transform: uppercase; padding: 1.3mm 1mm; text-align: left; border: .2mm solid #333; }
  table.grid td { border: .2mm solid #999; padding: 1mm; font-size: 8.2pt; vertical-align: middle; }
  table.grid td.num { text-align: right; }
  td .devise { font-weight: 700; font-size: 7.4pt; margin-right: 1.2mm; }
  .hint { font-size: 7.6pt; color: #555; margin-bottom: 1mm; }
  .certification { font-size: 8.4pt; padding: 1.6mm 2mm; border-left: 2px solid #333; margin: 2.6mm 0 1.6mm; }
  .date-sig { display: flex; justify-content: space-between; align-items: flex-start; gap: 8mm; }
  .date-sig .left { width: 70mm; }
  .sig-title { font-size: 7.6pt; font-weight: 600; color: #333; text-align: right; margin-bottom: 1mm; }
  .sig-box { border: .25mm solid #555; border-radius: 1mm; height: 18mm; }
  .sig-box.tall { height: 22mm; }
  .douanes-box { border: .35mm solid #111; border-radius: 1.5mm; padding: 2mm 2.4mm; margin-top: 1mm; }
  .douanes-title { text-align: center; font-weight: 700; font-size: 8.8pt; margin-bottom: 1.4mm; }
  .decl-row { display: flex; align-items: center; gap: 2mm; margin: 1.4mm 0; }
  .decl-row label { font-size: 8pt; font-weight: 700; color: #555; text-transform: uppercase; white-space: nowrap; }
  .decl-num { font-size: 12pt; font-weight: 700; text-align: center; min-width: 26mm; border: .2mm solid #ccc; border-radius: 1mm; padding: .8mm 2mm; background: #f7f9fc; }
  .muted { font-size: 7.4pt; color: #777; font-style: italic; }
  .strong-line { font-size: 8pt; font-weight: 700; }
  p { margin: 0; }
`

const fieldHtml = (label, value, strong = false) =>
  `<div class="field"><label>${esc(label)}</label><div class="v${strong ? ' strong' : ''}">${esc(value)}</div></div>`

const buildDocumentHtml = ({ form, derived }) => {
  const goodsRows = (form.goods || [])
    .map(r => `<tr>
      <td>${esc(r.tarif)}</td>
      <td>${esc(r.quantite)}</td>
      <td>${esc(r.poids)}</td>
      <td class="num">${esc(r.valeur)}</td>
    </tr>`)
    .join('') || '<tr><td></td><td></td><td></td><td class="num"></td></tr>'

  return `
    <div class="doc-title">ATTESTATION D'IMPORTATION</div>
    <div class="num-code">N° CODE : <b>${esc(form.id_number)}</b></div>

    <div class="grid-2">
      <div>
        <div class="section-label">Importateur</div>
        ${fieldHtml('Raison sociale', form.company_name, true)}
        ${form.imp_adresse ? fieldHtml('Adresse', form.imp_adresse) : ''}
        ${fieldHtml('NIF', form.id_number)}
        ${fieldHtml('Téléphone', form.phone)}
        ${fieldHtml('Ville / Pays', form.city)}
      </div>
      <div>
        <div class="section-label">Fournisseur / Expéditeur</div>
        ${fieldHtml('Nom', form.fournisseur_nom, true)}
        ${fieldHtml('Adresse', form.fournisseur_adresse)}
        ${fieldHtml('Téléphone', form.fournisseur_tel)}
        ${fieldHtml('Pays', form.fournisseur_pays)}
      </div>
    </div>

    <hr class="sep">

    <div class="section-label">Régime douanier</div>
    <div class="grid-4">
      ${fieldHtml('Régime', form.regime)}
      ${fieldHtml('Origine', form.origine)}
      ${fieldHtml('Provenance', form.provenance)}
      ${fieldHtml('N° Facture', form.num_facture)}
    </div>

    <div class="section-label">Marchandises importées</div>
    ${fieldHtml('Désignation de la marchandise', form.designation)}
    <table class="grid">
      <thead><tr>
        <th>N° tarif des douanes</th><th>Quantités importées</th><th>Poids Net</th><th>Valeur déclarée en douane (FCFA)</th>
      </tr></thead>
      <tbody>${goodsRows}</tbody>
    </table>

    <hr class="sep">

    <div class="section-label">Règlement financier</div>
    <div class="hint">Éléments de la valeur en douane (en francs CFA)</div>
    <table class="grid">
      <thead>
        <tr><th>Valeur FOB</th><th>Fret</th><th>Assurance</th><th>Ajustement</th><th>Valeur en douane (CFA)</th></tr>
      </thead>
      <tbody><tr>
        <td class="num">${esc(derived.valeur_fob)}</td>
        <td class="num">${esc(derived.fret)}</td>
        <td class="num">${esc(derived.assurance)}</td>
        <td class="num">${esc(derived.ajustement)}</td>
        <td class="num">${esc(derived.valeur_douane)}</td>
      </tr></tbody>
    </table>
    <table class="grid">
      <thead><tr>
        <th>Facture CAF (FCFA)</th><th>Facture CAF (Devise)</th><th>Facture en FOB</th><th>Facture franco dédouanée</th>
      </tr></thead>
      <tbody><tr>
        <td class="num"><span class="devise">FCFA</span>${esc(derived.facture_caf_fcfa)}</td>
        <td class="num"><span class="devise">${esc(form.devise_caf)}</span>${esc(form.facture_caf)}</td>
        <td class="num"><span class="devise">${esc(form.devise_caf)}</span>${esc(derived.facture_fob)}</td>
        <td class="num"><span class="devise">${esc(form.devise_franco)}</span>${esc(form.facture_franco)}</td>
      </tr></tbody>
    </table>

    <hr class="sep">

    <div class="certification">Je soussigné, certifie sincères et agréables les indications portées sur la présente formule.</div>
    <div class="date-sig">
      <div class="left">
        ${fieldHtml('Date', formatDate(form.date_declaration))}
        ${fieldHtml('En devise ou en francs selon le pays', deviseLabel(form.devise))}
      </div>
      <div class="right" style="flex:1">
        <div class="sig-title">Cachet et signature du déclarant</div>
        <div class="sig-box tall"></div>
      </div>
    </div>

    <hr class="sep">

    <div class="grid-2">
      <div>
        <div class="section-label">Banque intermédiaire agréé</div>
        ${fieldHtml('N° du dossier de domiciliation', form.num_dossier)}
        ${fieldHtml('Titulaire du dossier de domiciliation', form.titulaire_dossier)}
        <div class="muted">(S'il est différent du destinataire réel)</div>
        <div class="strong-line" style="margin-top:2mm">Cachet et Signature — Banque domiciliataire</div>
        <div class="sig-box tall" style="margin-top:1mm"></div>
      </div>
      <div>
        <div class="section-label">Douanes Togolaises</div>
        <div class="douanes-box">
          <div class="douanes-title">DOUANES TOGOLAISES</div>
          ${fieldHtml('Bureau N°', form.bureau)}
          <div class="decl-row">
            <label>Déclaration C N°</label>
            <div class="decl-num">${esc(form.declaration_num)}</div>
            <label>du</label>
            <div class="v" style="flex:1">${esc(formatDate(form.date_enregistrement))}</div>
          </div>
          <div class="strong-line" style="text-align:center; margin-top:1mm">Signature (cachet)</div>
          <div class="sig-box tall" style="margin-top:1mm"></div>
        </div>
      </div>
    </div>
  `
}

export const printAttestation = (data, onError) => {
  try {
    const iframe = document.createElement('iframe')

    iframe.setAttribute('aria-hidden', 'true')
    iframe.style.cssText = 'position:fixed; right:0; bottom:0; width:0; height:0; border:0; visibility:hidden;'
    document.body.appendChild(iframe)

    const win = iframe.contentWindow
    const doc = win.document

    doc.open()
    doc.write(`<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Attestation d'importation</title><style>${PRINT_CSS}</style></head><body><div id="print-page"><div id="scale-box"><div class="document">${buildDocumentHtml(data)}</div></div></div></body></html>`)
    doc.close()

    const fitToA4 = () => {
      const page = doc.getElementById('print-page')
      const scaleBox = doc.getElementById('scale-box')
      if (!page || !scaleBox)
        return

      const style = win.getComputedStyle(page)
      const padX = (parseFloat(style.paddingLeft) || 0) + (parseFloat(style.paddingRight) || 0)
      const padY = (parseFloat(style.paddingTop) || 0) + (parseFloat(style.paddingBottom) || 0)
      const availW = page.clientWidth - padX
      const availH = page.clientHeight - padY

      scaleBox.style.transform = 'none'
      scaleBox.style.width = `${availW}px`

      const neededH = scaleBox.scrollHeight
      let scale = Math.min(1, availH / neededH)
      if (!isFinite(scale) || scale <= 0)
        scale = 1
      scale = Math.floor(scale * 1000) / 1000

      scaleBox.style.width = `${availW / scale}px`
      scaleBox.style.transform = `scale(${scale})`
    }

    const cleanup = () => setTimeout(() => iframe.remove(), 1500)

    const go = () => {
      try {
        fitToA4()
        win.focus()
        win.print()
      }
      catch (e) {
        onError?.(e)
      }
      finally {
        cleanup()
      }
    }

    if (doc.fonts && doc.fonts.ready)
      doc.fonts.ready.then(() => setTimeout(go, 150)).catch(() => setTimeout(go, 150))
    else
      setTimeout(go, 250)
  }
  catch (e) {
    onError?.(e)
  }
}
