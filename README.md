# Family Find Android

This repository contains an Android-only MVP for your family device finder app with these features:

- email/password login and signup
- device registration in Firestore
- device dashboard with battery, online/offline, and last seen
- ring modes: normal, emergency, flashlight + vibration, continuous
- admin panel inside the app to search a family member by email and queue ring commands
- Firebase-ready push messaging hook

## Current project status

The codebase is scaffolded and wired for Firebase, but it was not built locally in this workspace because the machine does not currently have Android SDK / Gradle wrapper binaries available.

## Project structure

- `app/src/main/java/com/noxfind/familyfind/MainActivity.kt`: main Compose UI, auth flow, dashboard, admin panel, command listener
- `app/src/main/java/com/noxfind/familyfind/data/Repositories.kt`: Firebase auth + Firestore device and command repositories
- `app/src/main/java/com/noxfind/familyfind/ring/RingService.kt`: foreground service for ring modes
- `app/src/main/java/com/noxfind/familyfind/notifications/FamilyFindMessagingService.kt`: FCM service placeholder

## Firebase setup

1. Create a Firebase project.
2. Add an Android app with package name `com.noxfind.familyfind`.
3. Download `google-services.json` and place it at `app/google-services.json`.
4. Enable `Authentication > Email/Password`.
5. Create Firestore in production or test mode.
6. Optional: enable Firebase Cloud Messaging if you want to extend queued commands into true remote push delivery while the app is backgrounded.

## Firestore collections

### `users`
Document id: Firebase UID

```json
{
  "id": "uid",
  "email": "family@example.com",
  "isAdmin": true
}
```

### `devices`
Document id: current device id (`Build.ID` fallback)

```json
{
  "id": "device-id",
  "ownerUserId": "uid",
  "ownerEmail": "family@example.com",
  "name": "Samsung SM-A54",
  "model": "SM-A54",
  "batteryPercent": 72,
  "isOnline": true,
  "pushToken": "fcm-token",
  "updatedAtEpochMs": 1775350000000
}
```

### `commands`
Auto-generated document id

```json
{
  "targetDeviceId": "device-id",
  "targetUserEmail": "family@example.com",
  "requestedByEmail": "admin@example.com",
  "ringMode": "EMERGENCY",
  "createdAtEpochMs": 1775350000000,
  "status": "queued"
}
```

## First admin user

The current sign-up flow creates every new user with `isAdmin = false`.
After you create your own account, change that Firestore user document manually to:

```json
{
  "isAdmin": true
}
```

## What works in the current MVP

- sign in / sign up
- register current Android device to Firestore
- display your own devices
- search another family member by email
- queue ring commands for that device
- listen for incoming queued commands on the target device while the app is active
- play alarm/vibration/torch ring modes

## Important limitations for this first version

- The app currently uses Firestore command listening for device-to-device control. That means the target device should have the app running or recently active for the quickest response.
- `FamilyFindMessagingService` is included as the place to add full background push handling later.
- Flashlight behavior depends on hardware torch support.
- There is no stop button yet for continuous or emergency mode.
- `google-services.json` is intentionally not committed because it is project-specific.
- The Gradle wrapper jar and Android SDK are not present in this workspace, so the app was not compiled here.

## Next recommended steps

1. Add `google-services.json`.
2. Open the project in Android Studio.
3. Let Android Studio install the Android SDK and generate the missing Gradle wrapper files if needed.
4. Mark your own Firestore `users/{uid}` document as admin.
5. Test with two Android phones signed into different family accounts.

## Suggested next implementation tasks

- add an admin-only gate using `isAdmin`
- add a stop-ringing action
- turn queued commands into background FCM push delivery
- replace device id generation with `Settings.Secure.ANDROID_ID`
- add periodic WorkManager sync for fresher battery and last-seen updates
