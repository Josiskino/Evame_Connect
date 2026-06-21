<script setup>
import { VForm } from 'vuetify/components/VForm'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'created'])

const isOpen = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const today = new Date().toISOString().slice(0, 10)

const blankForm = () => ({
  nom: '',
  telephone: '',
  email: '',
  adresse: '',
  cni_recto: null,
  cni_verso: null,
  cni_date_emission: '',
  cni_date_expiration: '',
  cni_lieu_emission: '',
})

const refForm = ref()
const saving = ref(false)
const form = ref(blankForm())
const fieldErrors = ref({})

// Réinitialise le formulaire à chaque ouverture
watch(isOpen, open => {
  if (open) {
    form.value = blankForm()
    fieldErrors.value = {}
  }
})

// La CNI doit être valide : expiration strictement postérieure à aujourd'hui
const cniExpiree = computed(() => form.value.cni_date_expiration && form.value.cni_date_expiration <= today)

// Le bouton d'enregistrement reste grisé tant que les conditions ne sont pas réunies
const canSubmit = computed(() => Boolean(
  form.value.nom
  && form.value.telephone
  && form.value.cni_date_expiration
  && !cniExpiree.value,
))

const submit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid || !canSubmit.value)
    return

  saving.value = true
  fieldErrors.value = {}
  try {
    // multipart/form-data pour transporter les photos de la CNI
    const fd = new FormData()
    fd.append('nom', form.value.nom)
    fd.append('telephone', form.value.telephone)
    fd.append('cni_date_expiration', form.value.cni_date_expiration)
    if (form.value.email) fd.append('email', form.value.email)
    if (form.value.adresse) fd.append('adresse', form.value.adresse)
    if (form.value.cni_date_emission) fd.append('cni_date_emission', form.value.cni_date_emission)
    if (form.value.cni_lieu_emission) fd.append('cni_lieu_emission', form.value.cni_lieu_emission)
    if (form.value.cni_recto) fd.append('cni_recto', form.value.cni_recto)
    if (form.value.cni_verso) fd.append('cni_verso', form.value.cni_verso)

    const res = await $api('/clients', { method: 'POST', body: fd })

    emit('created', res?.data)
    isOpen.value = false
  }
  catch (err) {
    fieldErrors.value = err?.response?._data?.errors ?? {}
  }
  finally {
    saving.value = false
  }
}
</script>

<template>
  <VDialog v-model="isOpen" max-width="620" persistent scrollable>
    <VCard>
      <VCardItem>
        <VCardTitle>Nouveau client</VCardTitle>
        <VCardSubtitle>Renseignez les informations et la pièce d'identité (CNI).</VCardSubtitle>
      </VCardItem>

      <VCardText style="max-block-size: 70vh;">
        <VForm ref="refForm" @submit.prevent="submit">
          <VRow>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="form.nom"
                label="Nom complet *"
                placeholder="KOFFI Mensah"
                :rules="[requiredValidator]"
                :error-messages="fieldErrors.nom"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="form.telephone"
                label="Téléphone *"
                placeholder="+228 90 00 00 00"
                :rules="[requiredValidator]"
                :error-messages="fieldErrors.telephone"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="form.email"
                label="E-mail"
                type="email"
                placeholder="client@example.com"
                :error-messages="fieldErrors.email"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="form.adresse"
                label="Adresse"
                placeholder="Tokoin, Lomé"
                :error-messages="fieldErrors.adresse"
              />
            </VCol>

            <VCol cols="12">
              <VDivider class="my-1" />
              <div class="text-subtitle-2 text-medium-emphasis mt-2">Pièce d'identité (CNI)</div>
            </VCol>

            <VCol cols="12" sm="6">
              <VFileInput
                v-model="form.cni_recto"
                label="Photo CNI (recto)"
                accept="image/*"
                prepend-icon=""
                prepend-inner-icon="tabler-id"
                :error-messages="fieldErrors.cni_recto"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VFileInput
                v-model="form.cni_verso"
                label="Photo CNI (verso)"
                accept="image/*"
                prepend-icon=""
                prepend-inner-icon="tabler-id"
                :error-messages="fieldErrors.cni_verso"
              />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="form.cni_date_emission"
                label="Date d'émission"
                type="date"
                :error-messages="fieldErrors.cni_date_emission"
              />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="form.cni_date_expiration"
                label="Date d'expiration *"
                type="date"
                :error-messages="fieldErrors.cni_date_expiration"
              />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="form.cni_lieu_emission"
                label="Lieu d'émission"
                placeholder="Lomé"
                :error-messages="fieldErrors.cni_lieu_emission"
              />
            </VCol>

            <VCol v-if="cniExpiree" cols="12">
              <VAlert type="error" variant="tonal" density="compact">
                La CNI est expirée : la date d'expiration ({{ form.cni_date_expiration }}) doit être
                postérieure à aujourd'hui. Enregistrement impossible.
              </VAlert>
            </VCol>
            <VCol v-else-if="!form.cni_date_expiration" cols="12">
              <VAlert type="info" variant="tonal" density="compact">
                Renseignez une date d'expiration valide (future) pour activer l'enregistrement.
              </VAlert>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />
      <VCardText class="d-flex justify-end gap-3">
        <VBtn variant="tonal" color="secondary" :disabled="saving" @click="isOpen = false">
          Annuler
        </VBtn>
        <VBtn :loading="saving" :disabled="!canSubmit" @click="submit">
          Enregistrer
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
