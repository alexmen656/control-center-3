// msalConfig.js
import { PublicClientApplication } from "@azure/msal-browser";

export const msalInstance = new PublicClientApplication({
  auth: {
    clientId: process.env.VUE_APP_MSAL_CLIENT_ID,
    authority: process.env.VUE_APP_MSAL_AUTHORITY,
    redirectUri: process.env.VUE_APP_MSAL_REDIRECT_URI,
  },
});
