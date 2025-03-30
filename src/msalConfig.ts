// msalConfig.js
import { PublicClientApplication } from "@azure/msal-browser";

export const msalInstance = new PublicClientApplication({
  auth: {
    clientId: import.meta.env.VITE_APP_MSAL_CLIENT_ID,
    authority: import.meta.env.VITE_APP_MSAL_AUTHORITY,
    redirectUri: import.meta.env.VITE_APP_MSAL_REDIRECT_URI,
  },
});
