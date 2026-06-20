<script setup>
/* eslint-disable camelcase -- champs du document officiel alignés sur le contrat backend */
import { formatCafInput } from '@/composables/useAttestationCalc'
import { deviseSelectItems } from '@/utils/attestation-devises'

defineProps({
  derived: { type: Object, required: true },
  countryItems: { type: Array, default: () => [] },
})

const emit = defineEmits(['pick-importer', 'pick-supplier'])

const form = defineModel('form', { type: Object, required: true })

const deviseCodes = deviseSelectItems(true)
const deviseLabels = deviseSelectItems(false)

const addGoodsRow = () => {
  form.value.goods.push({ tarif: '', quantite: '', poids: '', valeur: '' })
}

const removeGoodsRow = index => {
  if (form.value.goods.length <= 1)
    return
  form.value.goods.splice(index, 1)
}

const onValeurInput = row => {
  row.valeur = formatCafInput(row.valeur)
}

const onFactureCafInput = () => {
  form.value.facture_caf = formatCafInput(form.value.facture_caf)
}
</script>

<template>
  <VCard
    rounded="lg"
    class="attestation-paper"
  >
    <VCardText class="pa-6 pa-md-8">
      <h1 class="doc-title">
        ATTESTATION D'IMPORTATION
      </h1>
      <div class="num-code">
        N° CODE : <span>{{ form.id_number }}</span>
      </div>

      <!-- IMPORTATEUR / FOURNISSEUR -->
      <VRow>
        <VCol
          cols="12"
          md="6"
        >
          <div class="section-head">
            <span class="section-title">Importateur</span>
            <VBtn
              size="small"
              variant="tonal"
              color="primary"
              prepend-icon="tabler-address-book"
              @click="emit('pick-importer')"
            >
              Carnet
            </VBtn>
          </div>
          <AppTextField
            v-model="form.company_name"
            label="Raison sociale"
            density="comfortable"
            class="mb-3"
          />
          <AppTextField
            v-model="form.imp_adresse"
            label="Adresse"
            placeholder="Adresse..."
            density="comfortable"
            class="mb-3"
          />
          <AppTextField
            v-model="form.id_number"
            label="NIF"
            placeholder="NIF..."
            density="comfortable"
            class="mb-3"
          />
          <AppTextField
            v-model="form.phone"
            label="Téléphone"
            placeholder="Tél..."
            density="comfortable"
            class="mb-3"
          />
          <AppTextField
            v-model="form.city"
            label="Ville / Pays"
            placeholder="Ville / Pays..."
            density="comfortable"
          />
        </VCol>
        <VCol
          cols="12"
          md="6"
        >
          <div class="section-head">
            <span class="section-title">Fournisseur / Expéditeur</span>
            <VBtn
              size="small"
              variant="tonal"
              color="primary"
              prepend-icon="tabler-address-book"
              @click="emit('pick-supplier')"
            >
              Carnet
            </VBtn>
          </div>
          <AppTextField
            v-model="form.fournisseur_nom"
            label="Nom du fournisseur"
            placeholder="Nom du fournisseur..."
            density="comfortable"
            class="mb-3"
          />
          <AppTextField
            v-model="form.fournisseur_adresse"
            label="Adresse"
            placeholder="Adresse complète..."
            density="comfortable"
            class="mb-3"
          />
          <AppTextField
            v-model="form.fournisseur_tel"
            label="Téléphone"
            placeholder="Tél..."
            density="comfortable"
            class="mb-3"
          />
          <AppAutocomplete
            v-model="form.fournisseur_pays"
            label="Pays"
            :items="countryItems"
            density="comfortable"
            auto-select-first
            placeholder="Pays..."
          />
        </VCol>
      </VRow>

      <VDivider class="my-6" />

      <!-- REGIME -->
      <div class="section-title mb-4">
        Régime douanier
      </div>
      <VRow>
        <VCol
          cols="12"
          sm="6"
          md="3"
        >
          <AppTextField
            v-model="form.regime"
            label="Régime"
            density="comfortable"
          />
        </VCol>
        <VCol
          cols="12"
          sm="6"
          md="3"
        >
          <AppAutocomplete
            v-model="form.origine"
            label="Origine"
            :items="countryItems"
            density="comfortable"
            auto-select-first
            placeholder="Pays..."
          />
        </VCol>
        <VCol
          cols="12"
          sm="6"
          md="3"
        >
          <AppAutocomplete
            v-model="form.provenance"
            label="Provenance"
            :items="countryItems"
            density="comfortable"
            auto-select-first
            placeholder="Pays..."
          />
        </VCol>
        <VCol
          cols="12"
          sm="6"
          md="3"
        >
          <AppTextField
            v-model="form.num_facture"
            label="N° Facture"
            placeholder="N° facture..."
            density="comfortable"
          />
        </VCol>
      </VRow>

      <!-- MARCHANDISES -->
      <div class="section-title mt-6 mb-4">
        Marchandises importées
      </div>
      <AppTextField
        v-model="form.designation"
        label="Désignation de la marchandise"
        placeholder="Désignation..."
        density="comfortable"
        class="mb-4"
      />
      <VTable class="goods-table text-no-wrap">
        <thead>
          <tr>
            <th>N° tarif des douanes</th>
            <th>Quantités importées</th>
            <th>Poids net</th>
            <th>Valeur déclarée en douane (FCFA)</th>
            <th style="width: 48px;" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, index) in form.goods"
            :key="index"
          >
            <td>
              <VTextField
                v-model="row.tarif"
                variant="plain"
                density="compact"
                hide-details
                placeholder="N° tarif"
              />
            </td>
            <td>
              <VTextField
                v-model="row.quantite"
                variant="plain"
                density="compact"
                hide-details
                placeholder="Quantité"
              />
            </td>
            <td>
              <VTextField
                v-model="row.poids"
                variant="plain"
                density="compact"
                hide-details
                placeholder="Poids"
              />
            </td>
            <td>
              <VTextField
                v-model="row.valeur"
                variant="plain"
                density="compact"
                hide-details
                placeholder="FCFA..."
                class="text-right-input"
                @input="onValeurInput(row)"
              />
            </td>
            <td class="text-center">
              <VBtn
                icon="tabler-x"
                size="x-small"
                variant="text"
                color="error"
                @click="removeGoodsRow(index)"
              />
            </td>
          </tr>
        </tbody>
      </VTable>
      <VBtn
        size="small"
        variant="tonal"
        color="primary"
        prepend-icon="tabler-plus"
        class="mt-3"
        @click="addGoodsRow"
      >
        Ajouter une ligne
      </VBtn>

      <VDivider class="my-6" />

      <!-- REGLEMENT FINANCIER -->
      <div class="section-title mb-1">
        Règlement financier
      </div>
      <div class="text-caption text-medium-emphasis mb-4">
        Éléments de la valeur en douane (en francs CFA)
      </div>
      <VRow>
        <VCol
          cols="6"
          md="2"
        >
          <AppTextField
            label="Valeur FOB"
            :model-value="derived.valeur_fob"
            density="comfortable"
            readonly
            class="computed-field"
          />
        </VCol>
        <VCol
          cols="6"
          md="2"
        >
          <AppTextField
            label="Fret"
            :model-value="derived.fret"
            density="comfortable"
            readonly
            class="computed-field"
          />
        </VCol>
        <VCol
          cols="6"
          md="2"
        >
          <AppTextField
            label="Assurance"
            :model-value="derived.assurance"
            density="comfortable"
            readonly
            class="computed-field"
          />
        </VCol>
        <VCol
          cols="6"
          md="3"
        >
          <AppTextField
            label="Ajustement"
            :model-value="derived.ajustement"
            density="comfortable"
            readonly
            class="computed-field"
          />
        </VCol>
        <VCol
          cols="12"
          md="3"
        >
          <AppTextField
            label="Valeur en douane (CFA)"
            :model-value="derived.valeur_douane"
            density="comfortable"
            readonly
            class="computed-field"
          />
        </VCol>
      </VRow>
      <VRow>
        <VCol
          cols="12"
          md="3"
        >
          <AppTextField
            label="Facture CAF (FCFA)"
            :model-value="derived.facture_caf_fcfa"
            density="comfortable"
            readonly
            prefix="FCFA"
            class="computed-field"
          />
        </VCol>
        <VCol
          cols="12"
          md="3"
        >
          <VLabel class="mb-1 text-body-2">
            Facture CAF (Devise)
          </VLabel>
          <div class="d-flex gap-2">
            <AppSelect
              v-model="form.devise_caf"
              :items="deviseCodes"
              density="comfortable"
              style="max-width: 96px;"
            />
            <VTextField
              v-model="form.facture_caf"
              variant="outlined"
              density="comfortable"
              placeholder="Montant"
              hide-details
              @input="onFactureCafInput"
            />
          </div>
        </VCol>
        <VCol
          cols="12"
          md="3"
        >
          <AppTextField
            :label="`Facture en FOB (${form.devise_caf})`"
            :model-value="derived.facture_fob"
            density="comfortable"
            readonly
            class="computed-field"
          />
        </VCol>
        <VCol
          cols="12"
          md="3"
        >
          <VLabel class="mb-1 text-body-2">
            Facture franco dédouanée
          </VLabel>
          <div class="d-flex gap-2">
            <AppSelect
              v-model="form.devise_franco"
              :items="deviseCodes"
              density="comfortable"
              style="max-width: 96px;"
            />
            <VTextField
              v-model="form.facture_franco"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </div>
        </VCol>
      </VRow>

      <VDivider class="my-6" />

      <!-- CERTIFICATION -->
      <VAlert
        variant="tonal"
        color="primary"
        density="comfortable"
        class="certification mb-4"
      >
        Je soussigné, certifie sincères et agréables les indications portées sur la présente formule.
      </VAlert>
      <VRow>
        <VCol
          cols="12"
          md="6"
        >
          <AppTextField
            v-model="form.date_declaration"
            label="Date"
            type="date"
            density="comfortable"
            class="mb-3"
            style="max-width: 260px;"
          />
          <AppSelect
            v-model="form.devise"
            label="En devise ou en francs selon le pays"
            :items="deviseLabels"
            density="comfortable"
            style="max-width: 320px;"
          />
        </VCol>
        <VCol
          cols="12"
          md="6"
          class="d-flex flex-column align-md-end"
        >
          <span class="text-body-2 text-medium-emphasis mb-1">Cachet et signature du déclarant</span>
          <div class="sig-box" />
        </VCol>
      </VRow>

      <VDivider class="my-6" />

      <!-- BANQUE / DOUANES -->
      <VRow>
        <VCol
          cols="12"
          md="6"
        >
          <div class="section-title mb-4">
            Banque intermédiaire agréé
          </div>
          <AppTextField
            v-model="form.num_dossier"
            label="N° du dossier de domiciliation"
            placeholder="N° de dossier..."
            density="comfortable"
            class="mb-3"
          />
          <AppTextField
            v-model="form.titulaire_dossier"
            label="Titulaire du dossier de domiciliation"
            placeholder="Nom du titulaire..."
            density="comfortable"
          />
          <div class="text-caption text-medium-emphasis font-italic mt-1 mb-3">
            (S'il est différent du destinataire réel)
          </div>
          <div class="text-body-2 font-weight-bold mb-1">
            Cachet et signature — Banque domiciliataire
          </div>
          <div class="sig-box" />
        </VCol>
        <VCol
          cols="12"
          md="6"
        >
          <div class="section-title mb-4">
            Douanes Togolaises
          </div>
          <VCard
            variant="outlined"
            rounded="lg"
          >
            <VCardText>
              <div class="text-center text-subtitle-1 font-weight-bold mb-4">
                DOUANES TOGOLAISES
              </div>
              <AppTextField
                v-model="form.bureau"
                label="Bureau N°"
                density="comfortable"
                class="mb-3"
              />
              <div class="d-flex align-center flex-wrap gap-2 mb-3">
                <span class="text-body-2 font-weight-bold text-uppercase">Déclaration C N°</span>
                <VTextField
                  v-model="form.declaration_num"
                  variant="outlined"
                  density="comfortable"
                  hide-details
                  class="decl-num-field"
                />
                <span class="text-body-2 font-weight-bold text-uppercase">du</span>
                <VTextField
                  v-model="form.date_enregistrement"
                  type="date"
                  variant="outlined"
                  density="comfortable"
                  hide-details
                  style="max-width: 170px;"
                />
              </div>
              <div class="text-center text-body-2 font-weight-medium text-medium-emphasis mb-1">
                Signature (cachet)
              </div>
              <div class="sig-box" />
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<style scoped>
.attestation-paper {
  background: rgb(var(--v-theme-surface));
}

.doc-title {
  text-align: center;
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 1px;
  text-decoration: underline;
  text-underline-offset: 6px;
  text-decoration-thickness: 2px;
  margin-bottom: 1rem;
  color: rgb(var(--v-theme-on-surface));
}

.num-code {
  text-align: right;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 1.25rem;
}
.num-code span { font-weight: 400; }

.section-title {
  display: block;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgb(var(--v-theme-primary));
  padding-bottom: 4px;
  border-bottom: 2px solid rgba(var(--v-theme-primary), 0.25);
}

.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 1rem;
}
.section-head .section-title {
  border-bottom: none;
  padding-bottom: 0;
}

.certification {
  font-size: 0.9rem;
}

.sig-box {
  width: 100%;
  min-height: 92px;
  border: 1px dashed rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  background:
    repeating-linear-gradient(
      -45deg,
      rgba(var(--v-theme-on-surface), 0.015),
      rgba(var(--v-theme-on-surface), 0.015) 8px,
      transparent 8px,
      transparent 16px
    );
}

.decl-num-field {
  max-width: 120px;
}
.decl-num-field :deep(input) {
  text-align: center;
  font-size: 1.05rem;
  font-weight: 700;
}

/* Champs calculés : léger fond pour signaler la lecture seule. */
.computed-field :deep(.v-field) {
  background: rgba(var(--v-theme-primary), 0.04);
}

.text-right-input :deep(input) { text-align: right; }

.goods-table :deep(th) {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.goods-table :deep(td) { padding-block: 2px; }
</style>
