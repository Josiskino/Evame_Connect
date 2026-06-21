<script setup>
import html2pdf from 'html2pdf.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  contrat: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const contractRef = ref()
const working = ref(false)

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`
const fmtDate = d => (d ? new Intl.DateTimeFormat('fr-FR').format(new Date(d)) : '—')

const freqLabel = { journalier: 'Journalier', hebdomadaire: 'Hebdomadaire', mensuel: 'Mensuel' }

const echeancier = computed(() => {
  const c = props.contrat
  if (!c) return { hebdomadaire: 0, mensuel: 0, nombre_mois: 0 }
  const mois = Math.max(1, Math.ceil(c.duree_jours / 30))

  return {
    hebdomadaire: c.montant_journalier * 7,
    nombre_mois: mois,
    mensuel: Math.round(c.montant_total / mois),
  }
})

const reference = computed(() => `EVAME-LSG-${String(props.contrat?.id ?? 0).padStart(5, '0')}`)

const pdfOptions = () => ({
  margin: [10, 10, 10, 10],
  filename: `contrat-leasing-${(props.contrat?.client?.nom ?? 'client').replace(/\s+/g, '-')}.pdf`,
  image: { type: 'jpeg', quality: 0.98 },
  html2canvas: { scale: 2, useCORS: true },
  jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
})

const download = async () => {
  working.value = true
  try {
    await html2pdf().set(pdfOptions()).from(contractRef.value).save()
  }
  finally { working.value = false }
}

const print = async () => {
  working.value = true
  try {
    const pdf = await html2pdf().set(pdfOptions()).from(contractRef.value).toPdf().get('pdf')
    pdf.autoPrint()
    window.open(pdf.output('bloburl'), '_blank')
  }
  finally { working.value = false }
}
</script>

<template>
  <VDialog v-model="isOpen" max-width="820" scrollable>
    <VCard v-if="contrat">
      <VCardItem>
        <VCardTitle>Aperçu du contrat</VCardTitle>
        <template #append>
          <VBtn icon variant="text" size="small" @click="isOpen = false"><VIcon icon="tabler-x" /></VBtn>
        </template>
      </VCardItem>

      <VCardText style="max-block-size: 70vh; background: #f4f4f4;">
        <!-- Document imprimable -->
        <div ref="contractRef" class="evame-contract">
          <div class="ec-head">
            <h1>CONTRAT DE LEASING MOTO</h1>
            <div class="ec-ref">
              <div>Réf. : <strong>{{ reference }}</strong></div>
              <div>Date : <strong>{{ fmtDate(contrat.date_debut) }}</strong></div>
            </div>
          </div>

          <div class="ec-parties">
            <div class="ec-box">
              <div class="ec-box-title">LE BAILLEUR</div>
              <div><strong>Groupe EVAME SA</strong></div>
              <div>Distribution de motos &amp; services</div>
              <div>Lomé, Togo</div>
            </div>
            <div class="ec-box">
              <div class="ec-box-title">LE PRENEUR</div>
              <div><strong>{{ contrat.client?.nom }}</strong></div>
              <div>Tél. : {{ contrat.client?.telephone || '—' }}</div>
              <div>Adresse : {{ contrat.client?.adresse || '—' }}</div>
              <div v-if="contrat.client?.cni_date_expiration">
                CNI valide jusqu'au {{ fmtDate(contrat.client.cni_date_expiration) }}
                <span v-if="contrat.client?.cni_lieu_emission"> ({{ contrat.client.cni_lieu_emission }})</span>
              </div>
            </div>
          </div>

          <div class="ec-section-title">ARTICLE 1 — OBJET</div>
          <p>
            Le présent contrat a pour objet la location avec option d'achat (leasing) du véhicule
            deux-roues suivant : <strong>{{ contrat.moto?.modele }}</strong>
            <span v-if="contrat.moto?.classe_cc"> ({{ contrat.moto.classe_cc }})</span>.
          </p>

          <div class="ec-section-title">ARTICLE 2 — CONDITIONS FINANCIÈRES</div>
          <table class="ec-table">
            <tbody>
              <tr><td>Date de début</td><td>{{ fmtDate(contrat.date_debut) }}</td></tr>
              <tr><td>Durée</td><td>{{ contrat.duree_jours }} jours</td></tr>
              <tr><td>Montant journalier de référence</td><td>{{ fmtMoney(contrat.montant_journalier) }}</td></tr>
              <tr><td>Fréquence de paiement</td><td>{{ freqLabel[contrat.frequence] || contrat.frequence }}</td></tr>
              <tr><td>Échéance hebdomadaire</td><td>{{ fmtMoney(echeancier.hebdomadaire) }}</td></tr>
              <tr><td>Échéance mensuelle ({{ echeancier.nombre_mois }} mois)</td><td>{{ fmtMoney(echeancier.mensuel) }}</td></tr>
              <tr class="ec-strong"><td>Montant total du contrat</td><td>{{ fmtMoney(contrat.montant_total) }}</td></tr>
            </tbody>
          </table>

          <div class="ec-section-title">ARTICLE 3 — ÉTAT DU REMBOURSEMENT</div>
          <table class="ec-table">
            <tbody>
              <tr><td>Déjà payé</td><td>{{ fmtMoney(contrat.montant_paye) }}</td></tr>
              <tr><td>Reste à payer</td><td>{{ fmtMoney(contrat.montant_restant) }}</td></tr>
              <tr><td>Progression</td><td>{{ contrat.progression }} %</td></tr>
              <tr><td>Statut</td><td>{{ contrat.en_retard ? 'En retard' : 'À jour' }}</td></tr>
            </tbody>
          </table>

          <div class="ec-signatures">
            <div><div class="ec-sign-label">Le Bailleur</div><div class="ec-sign-line" /></div>
            <div><div class="ec-sign-label">Le Preneur</div><div class="ec-sign-line" /></div>
          </div>

          <!-- Pied de page : logo + nom du groupe -->
          <div class="ec-footer">
            <img src="/logo-evame.png" alt="EVAME" crossorigin="anonymous" />
            <div>
              <div class="ec-footer-name">GROUPE EVAME SA</div>
              <div class="ec-footer-sub">Document généré par EVAME CONNECT — {{ reference }}</div>
            </div>
          </div>
        </div>
      </VCardText>

      <VDivider />
      <VCardText class="d-flex flex-wrap justify-end gap-3">
        <VBtn variant="tonal" color="secondary" :disabled="working" @click="isOpen = false">Fermer</VBtn>
        <VBtn variant="tonal" prepend-icon="tabler-printer" :loading="working" @click="print">Imprimer</VBtn>
        <VBtn prepend-icon="tabler-download" :loading="working" @click="download">Télécharger PDF</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.evame-contract {
  max-inline-size: 760px;
  margin-inline: auto;
  padding: 32px 36px;
  background: #fff;
  color: #1a1a1a;
  font-family: "Helvetica Neue", Arial, sans-serif;
  font-size: 13px;
  line-height: 1.55;

  .ec-head {
    padding-block-end: 12px;
    border-block-end: 2px solid #e53935;
    text-align: center;

    h1 { margin: 0; font-size: 20px; letter-spacing: 1px; }
  }

  .ec-ref {
    display: flex;
    justify-content: space-between;
    margin-block-start: 8px;
    font-size: 12px;
  }

  .ec-parties {
    display: flex;
    gap: 16px;
    margin-block: 18px;
  }

  .ec-box {
    flex: 1;
    padding: 12px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;

    .ec-box-title { margin-block-end: 6px; color: #e53935; font-size: 11px; font-weight: 700; letter-spacing: .5px; }
  }

  .ec-section-title {
    margin-block: 16px 8px;
    font-size: 13px;
    font-weight: 700;
    color: #222;
  }

  .ec-table {
    inline-size: 100%;
    border-collapse: collapse;

    td { padding: 7px 10px; border: 1px solid #e6e6e6; }
    td:first-child { color: #555; inline-size: 60%; }
    td:last-child { font-weight: 600; text-align: end; }

    .ec-strong td { background: #fff5f5; font-size: 14px; }
  }

  .ec-signatures {
    display: flex;
    justify-content: space-between;
    margin-block-start: 40px;

    .ec-sign-label { margin-block-end: 36px; font-size: 12px; color: #555; }
    .ec-sign-line { inline-size: 200px; border-block-start: 1px solid #333; }
  }

  .ec-footer {
    display: flex;
    gap: 12px;
    align-items: center;
    justify-content: center;
    margin-block-start: 36px;
    padding-block-start: 14px;
    border-block-start: 1px solid #e0e0e0;
    text-align: center;

    img { block-size: 38px; inline-size: auto; }

    .ec-footer-name { font-weight: 800; letter-spacing: 1px; color: #e53935; }
    .ec-footer-sub { font-size: 11px; color: #888; }
  }
}
</style>
