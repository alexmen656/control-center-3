import UIKit
import Capacitor
import SwiftUI


@UIApplicationMain
class AppDelegate: UIResponder, UIApplicationDelegate {
 
    var window: UIWindow?

    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {
        // Override point for customization after application launch.
        //SettingsBundleHelper.setTest()
        SettingsBundleHelper.setTest()
        print("lsod")
        Alert(
            title: Text("Important message"),
            message: Text("Wear sunscreen"),
            dismissButton: .default(Text("Got it!"))
        )

    
           // Auslesen des Werts für die Einstellung "email"
        let defaults = UserDefaults.standard
        defaults.setValue("fgfhf", forKey: "token")
        if let email = defaults.string(forKey: "email") {
            // Wert der Einstellung "email" ist verfügbar
            // Sende den Wert an das Widget
            let widgetUserDefaults = UserDefaults(suiteName: "group.settings-settings") // Ersetze "com.example.widget" durch die tatsächliche Gruppenkennung deines Widgets
            widgetUserDefaults?.set(email, forKey: "email")
            widgetUserDefaults?.synchronize()
        } else {
            // Kein Wert für die Einstellung "email" gefunden
        }

        
        
        func test(){
            
            // Die URL der API
            let apiUrl = URL(string: "https://api.example.com/data")!

            // URLSession erstellen
            let session = URLSession.shared

            // URLRequest erstellen
            var request = URLRequest(url: apiUrl)
            request.httpMethod = "GET"

            // URLSessionDataTask erstellen
            let task = session.dataTask(with: request) { (data, response, error) in
                if let error = error {
                    // Fehlerbehandlung
                    print("Fehler: \(error)")
                    return
                }
                
                // Erfolgreiche Antwort erhalten
                if let data = data {
                    // API-Antwort verarbeiten
                    let responseString = String(data: data, encoding: .utf8)
                    print("API-Antwort: \(responseString ?? "")")
                    
                    // Hier kannst du die erhaltenen Daten weiterverarbeiten
                }
            }

            // Aufruf starten
            task.resume()
        }
        test()
        return true

    }

    func applicationWillResignActive(_ application: UIApplication) {
        // Sent when the application is about to move from active to inactive state. This can occur for certain types of temporary interruptions (such as an incoming phone call or SMS message) or when the user quits the application and it begins the transition to the background state.
        // Use this method to pause ongoing tasks, disable timers, and invalidate graphics rendering callbacks. Games should use this method to pause the game.
    }

    func applicationDidEnterBackground(_ application: UIApplication) {
        // Use this method to release shared resources, save user data, invalidate timers, and store enough application state information to restore your application to its current state in case it is terminated later.
        // If your application supports background execution, this method is called instead of applicationWillTerminate: when the user quits.
    }

    func applicationWillEnterForeground(_ application: UIApplication) {
        // Called as part of the transition from the background to the active state; here you can undo many of the changes made on entering the background.
        Alert(
            title: Text("Important message"),
            message: Text("Wear sunscreen"),
            dismissButton: .default(Text("Got it!"))
        )
        SettingsBundleHelper.setTest()
    }

    func applicationDidBecomeActive(_ application: UIApplication) {
        // Restart any tasks that were paused (or not yet started) while the application was inactive. If the application was previously in the background, optionally refresh the user interface.
    }

    func applicationWillTerminate(_ application: UIApplication) {
        // Called when the application is about to terminate. Save data if appropriate. See also applicationDidEnterBackground:.
    }

    func application(_ app: UIApplication, open url: URL, options: [UIApplication.OpenURLOptionsKey: Any] = [:]) -> Bool {
        // Called when the app was launched with a url. Feel free to add additional processing here,
        // but if you want the App API to support tracking app url opens, make sure to keep this call
        return ApplicationDelegateProxy.shared.application(app, open: url, options: options)
    }

    func application(_ application: UIApplication, continue userActivity: NSUserActivity, restorationHandler: @escaping ([UIUserActivityRestoring]?) -> Void) -> Bool {
        // Called when the app was launched with an activity, including Universal Links.
        // Feel free to add additional processing here, but if you want the App API to support
        // tracking app url opens, make sure to keep this call
        return ApplicationDelegateProxy.shared.application(application, continue: userActivity, restorationHandler: restorationHandler)
    }

}
