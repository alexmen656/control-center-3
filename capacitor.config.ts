import { CapacitorConfig } from "@capacitor/cli";

const config: CapacitorConfig = {
  appId: process.env.VUE_APP_APP_ID || "eu.control_center.app",
  appName: process.env.VUE_APP_APP_NAME || "Control Center",
  webDir: process.env.VUE_APP_WEB_DIR || "dist",
  bundledWebRuntime: false,
  plugins: {
    SplashScreen: {
      launchShowDuration: parseInt(process.env.VUE_APP_SPLASH_LAUNCH_SHOW_DURATION) || 2000,
      launchAutoHide: process.env.VUE_APP_SPLASH_LAUNCH_AUTO_HIDE === "true",
      backgroundColor: process.env.VUE_APP_SPLASH_BACKGROUND_COLOR || "#000000",
      androidSplashResourceName: process.env.VUE_APP_ANDROID_SPLASH_RESOURCE_NAME || "splash",
      androidScaleType: process.env.VUE_APP_ANDROID_SCALE_TYPE || "CENTER_CROP",
      splashFullScreen: process.env.VUE_APP_SPLASH_FULL_SCREEN === "true",
      splashImmersive: process.env.VUE_APP_SPLASH_IMMERSIVE === "true",
      layoutName: process.env.VUE_APP_LAYOUT_NAME || "launch_screen",
      useDialog: process.env.VUE_APP_USE_DIALOG === "true",
    },
    GoogleAuth: {
      scopes: ["profile", "email"],
      serverClientId: process.env.GA_SCID,
      forceCodeForRefreshToken: true,
      iosClientId: process.env.GA_ICID
    },
    FirebaseMessaging: {
      presentationOptions: ["badge", "sound", "alert"]
    }
  },
};

export default config;
