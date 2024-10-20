import { CapacitorConfig } from "@capacitor/cli";

const config: CapacitorConfig = {
  appId: process.env.APP_ID || "sk.polan.alex.control-center",
  appName: process.env.APP_NAME || "Control Center",
  webDir: process.env.WEB_DIR || "dist",
  bundledWebRuntime: false,
  plugins: {
    SplashScreen: {
      launchShowDuration: parseInt(process.env.SPLASH_LAUNCH_SHOW_DURATION) || 2000,
      launchAutoHide: process.env.SPLASH_LAUNCH_AUTO_HIDE === "true",
      backgroundColor: process.env.SPLASH_BACKGROUND_COLOR || "#000000",
      androidSplashResourceName: process.env.ANDROID_SPLASH_RESOURCE_NAME || "splash",
      androidScaleType: process.env.ANDROID_SCALE_TYPE || "CENTER_CROP",
      splashFullScreen: process.env.SPLASH_FULL_SCREEN === "true",
      splashImmersive: process.env.SPLASH_IMMERSIVE === "true",
      layoutName: process.env.LAYOUT_NAME || "launch_screen",
      useDialog: process.env.USE_DIALOG === "true",
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
