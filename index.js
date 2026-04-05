const admin = require("firebase-admin");
const { onDocumentCreated } = require("firebase-functions/v2/firestore");
const { logger } = require("firebase-functions");

admin.initializeApp();

exports.sendCommandPush = onDocumentCreated("commands/{commandId}", async (event) => {
  const snapshot = event.data;
  if (!snapshot) {
    logger.warn("No command snapshot in event");
    return;
  }

  const commandId = event.params.commandId;
  const command = snapshot.data();
  const targetDeviceId = command.targetDeviceId;
  if (!targetDeviceId) {
    logger.warn("Command missing targetDeviceId", { commandId });
    return;
  }

  const deviceDoc = await admin.firestore().collection("devices").doc(targetDeviceId).get();
  if (!deviceDoc.exists) {
    logger.warn("Target device not found", { commandId, targetDeviceId });
    await snapshot.ref.update({ status: "device_not_found" });
    return;
  }

  const device = deviceDoc.data() || {};
  const pushToken = device.pushToken;
  if (!pushToken) {
    logger.warn("Target device missing push token", { commandId, targetDeviceId });
    await snapshot.ref.update({ status: "missing_push_token" });
    return;
  }

  const payload = {
    token: pushToken,
    data: {
      commandId,
      commandType: command.commandType || "RING",
      ringMode: command.ringMode || "NORMAL",
      targetDeviceId: targetDeviceId,
    },
    android: {
      priority: "high",
      ttl: 30000,
    },
  };

  try {
    await admin.messaging().send(payload);
    await snapshot.ref.update({ status: "push_sent" });
    logger.info("Command push sent", { commandId, targetDeviceId });
  } catch (error) {
    logger.error("Failed to send command push", { commandId, targetDeviceId, error });
    await snapshot.ref.update({ status: "push_failed", pushError: String(error.message || error) });
  }
});
