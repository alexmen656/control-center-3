import { CapacitorConfig } from "@capacitor/cli";

const config: CapacitorConfig = {
  appId: "sk.alex.polan.control-center",
  appName: "Control Center",
  webDir: "dist",
  bundledWebRuntime: false,
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      launchAutoHide: true,
      backgroundColor: "#000000",
      androidSplashResourceName: "splash",
      androidScaleType: "CENTER_CROP",
      splashFullScreen: true,
      splashImmersive: true,
      layoutName: "launch_screen",
      useDialog: true,
    },
    GoogleAuth: {
      scopes: ["profile", "email"],
      serverClientId:
        "706582238302-k3e6bqv81en6u97gf8l5pq883p773236.apps.googleusercontent.com",
      forceCodeForRefreshToken: true,
      iosClientId: "706582238302-q1i78up9i9p5ia3tmaurqeerll1ucthg.apps.googleusercontent.com"
    },
  },
};

export default config;
