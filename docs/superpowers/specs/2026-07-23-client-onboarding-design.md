# Client Onboarding Design

## Goal

Build a three-screen onboarding flow for `evame_client_apk` that introduces the client app before landing on the existing home screen.

## User Flow

- App starts on onboarding in the current development mode.
- Each onboarding screen displays one generated image as the full-screen visual background.
- Text is stacked at the bottom of the image over a dark gradient for readability.
- A `Passer` action is visible on every onboarding screen and sends the user directly to the current home experience.
- The primary button says `Suivant` on screens 1 and 2.
- The primary button says `Terminé` on screen 3.
- Pressing `Terminé` sends the user to the existing `MainPage`, which opens on the current home tab.

## Screens

1. `onboarding-discover-moto.png`
   - Title: `Trouvez votre prochaine moto`
   - Body: `Explorez les modèles disponibles et comparez les options qui correspondent à vos trajets.`

2. `onboarding-leasing-payments.png`
   - Title: `Financez plus simplement`
   - Body: `Visualisez vos mensualités et avancez étape par étape dans votre demande de leasing.`

3. `onboarding-service-tracking.png`
   - Title: `Suivez votre service`
   - Body: `Gardez le contrôle sur vos entretiens, interventions et demandes d’assistance.`

## Visual Design

- Use the existing generated images from `assets/images/onboarding/`.
- Use a full-screen `PageView` with `BoxFit.cover`.
- Place a dark bottom gradient above the image to protect text contrast.
- Keep controls stable at the bottom: title, body, dots, then primary button.
- Use EVAME orange from the existing app theme for the active dot and primary button.
- Keep the card radius tight on the button, around `8px`, matching the app's utilitarian mobile style.

## Architecture

- Add a route constant for onboarding.
- Add a dedicated onboarding page under `lib/presentation/pages/onboarding/`.
- Keep onboarding slide data local to the onboarding page because it is static and presentation-only.
- Route completion and skip actions to `AppRoute.main`.
- Update the router initial route to onboarding while `_bypassAuth` is active.

## Testing

- Add a widget test that verifies the first onboarding slide renders.
- Verify `Suivant` advances to the second slide.
- Verify the final slide shows `Terminé`.
- Verify `Passer` and `Terminé` trigger navigation to `AppRoute.main`.
