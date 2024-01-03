const axios = require("axios");
const { google } = require("googleapis");

let sentMessageIds = [];
const SCOPES = ["https://www.googleapis.com/auth/firebase.messaging"];

async function getAccessToken() {
  return new Promise(async function (resolve, reject) {
    const key = {
      type: "service_account",
      project_id: "control-center-2",
      private_key_id: "cfd0fc203eac20425849fa033f65514c8e35d2c9",
      private_key:
        "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDaycysGfkKG/a+\no/d5A7NxfHidApo04hH0zzANTGYZrq05lkhlUiBg4LadezawUWom+NPCwWWisEVA\nWRC7fRQYqaqV3XM8jO94sxXiD9kE0iNv6Kf2w5DYdq/CqimkaA906lHHB6IBPLC7\n9kM9Bat3B3P3RhWeMCECEQIyjB7kteurBngUZNvyGhaZhvakbef0V7nuEuSeFG0x\nEvgqM4umFlbyrHZInhgtt0QsnEc5SoENBtwHkR15uquG0z9Cwd3gZ8N3MvcLqo1L\nY0MWylCNpBmA3xlzKioOc/b7P8QSt4ZMD40pnl8pokp+Txdxs8yp9E8SYCT5NDrD\nTxHa4PafAgMBAAECggEACO1G7pa4tJLjhG/IPyXJgo3jlfRvOk0nEmgJu+EsIUwh\nQUJCfe/V5l4E1XQbjSIlRKXObhnZ/cNXcAKyThQSZ9c0YJ0CgKv3cWNNN0YSQsIY\nNA64G7drB4oALd8nh8+/IbQV1hsXnxlM1L59i9XZYyfosWQoqaWpEx6CinFxKT+3\notsGgo735BIQwYY5SzwGdnguXYOY0H3PxV5kMc+TS5hVn0wefAbSn1gAbe8Ax/R3\n31Z98gzNb/17S1KIORv0ItUbievaYBsCI0Y28SvrT2BctfOEK2J49Pv/0Q4bEZTr\ndTd26B4O9HcdEm/Q/WwUxLyREGv42tNcAWfO5NoSUQKBgQD3i9c1f08GbITxhPR7\nndELAr4yDydwdPKRuBo+D3dxrck7fTTXn3YLFvM8VDLlU/4FTBT0+ZnvmamRWbsm\n2YXy5njkHKDbEfkme28yE7FZ1l9Hv9dzEFsuFXBee62fUAniU0LPKVW6a/tacBTf\nd/4EBEwLpg1sg116R2Zm/GvJAwKBgQDiQos6Y4sqDblyF4q+SO2Amy792PzYrP9d\n8caaTOw/MfhcBUng5Yly79Y+Cd261tGS8MdFyDKVxPsmvIY8cUJPCqnoECVS7qWB\nyHPCLw3P6gppXvlHIy2KGRcMnyviimLdyKbRkkkdgqv118OPXDdknqLr0zyUMglp\nrF6WIGxzNQKBgQDkfnr2kX2TM2X/PTciR4jmffCraluALSKeKO5oLISXNM+Tjr3Q\n7grar6NzI1EbZ00I/LI3cZGKnS7s0IO0l4JRtDUQcfB0ZgGxaKw57/17LCoko0qu\nlgFj5zwiqkyXyhxlgW8go0nTWsrXLq/Fmg+pC4JaGjs314XYUcGO/B5NmwKBgF3u\n0m6DNRtYZ0z+iRNGo5No2bF5jD9IUxxla9ZTaSEzVbCeYXWE1fNprsBCyFLxLECc\nxZ4q5xVWmg4S5ofhXW4DN1aonVY4zW18EtLjRhCzUW89hrAJ4rYahH8w9b8vRKxe\nySLoYIJn5YxC72VtG3IiifDt2ZCM1WuRrBMXku6hAoGBALeM5ld59bW+QY95k5+X\na/t4SuxSaKBfBqgeCB4+1xJCwj+zLLVZpmYqaExtoNyIqgJycDan7XTxc/T88z8D\nBpV4INpWKzmiAFPM9NDPAmbGjqFidUdi7G4UfyjsVRt6bKs+3p2HeOAAsHdcNoQG\nag28wk801Mqx2FeE65yEWxQQ\n-----END PRIVATE KEY-----\n",
      client_email:
        "firebase-adminsdk-btio0@control-center-2.iam.gserviceaccount.com",
      client_id: "104048962774278753523",
      auth_uri: "https://accounts.google.com/o/oauth2/auth",
      token_uri: "https://oauth2.googleapis.com/token",
      auth_provider_x509_cert_url: "https://www.googleapis.com/oauth2/v1/certs",
      client_x509_cert_url:
        "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-btio0%40control-center-2.iam.gserviceaccount.com",
      universe_domain: "googleapis.com",
    };

    const jwtClient = new google.auth.JWT(
      key.client_email,
      null,
      key.private_key,
      SCOPES,
      null
    );

    try {
      const tokens = await jwtClient.authorize();
      resolve(tokens.access_token);
    } catch (err) {
      reject(err);
    }
  });
}

getAccessToken()
  .then((accessToken) => {
    function loadPushMessages2() {
      loadPushMessages();
    }

    async function loadPushMessages() {
      try {
        const response = await axios.get(
          "https://alex.polan.sk/control-center/push_messages.php"
        );
        const data = response.data.notifications;
        const currentTime = new Date();
        if (data.length > 0) {
          for (const message of data) {
            const { id, title, text, token } = message;

            if (!sentMessageIds.find((msg) => msg.id === id)) {
              let body = {
                message: {
                  token: token,
                  // "topic": "news",
                  notification: {
                    title: title,
                    body: text,
                  },
                  data: {
                    story_id: "story_12345",
                  },
                  ios: {
                    priority: "high",
                    notification: {
                      sound: "default",
                    },
                  },
                  android: {
                    notification: {
                      click_action: "TOP_STORY_ACTIVITY",
                    },
                  },
                  apns: {
                    payload: {
                      aps: {
                        category: "NEW_MESSAGE_CATEGORY",
                      },
                    },
                  },
                },
              };

              let options = {
                method: "POST",
                headers: new Headers({
                  Authorization: " Bearer " + accessToken,
                  "Content-Type": "application/json",
                }),
                body: JSON.stringify(body),
              };

              fetch(
                "https://fcm.googleapis.com/v1/projects/control-center-2/messages:send",
                options
              )
                .then(() => {
                  console.log("Sent to: ", token);
                })
                .catch((e) => console.log(e));

              if (sentMessageIds.push({ id, timestamp: currentTime })) {
                loadPushMessages2();
              }
            } else {
              setTimeout(loadPushMessages, 10000);
            }
          }

          sentMessageIds = sentMessageIds.filter(
            (msg) => currentTime - msg.timestamp <= 300 * 60 * 1000
          );
        } else {
          setTimeout(loadPushMessages, 10000);
        }
      } catch (error) {
        console.error("Fehler beim Laden der Push-Nachrichten:", error.message);
      }
    }

    function checkAndRemoveOldMessages() {
      const currentTime = new Date();
      sentMessageIds = sentMessageIds.filter(
        (msg) => currentTime - msg.timestamp <= 300 * 60 * 1000
      );
    }

    loadPushMessages();
    setInterval(checkAndRemoveOldMessages, 300 * 60 * 1000);
  })
  .catch((error) => {
    console.error("Error:", error);
  });
