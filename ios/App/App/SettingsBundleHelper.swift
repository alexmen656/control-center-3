//
//  SettingsBundleHelper.swift
//  App
//
//  Created by Alex Polan on 22/06/2023.
//

import Foundation
import UIKit
import SwiftUI

class SettingsBundleHelper {
    enum UserDefaultKeys: String {
        case Test = "Test"
    }
    
    
    class func setTest() {
        Alert(
            title: Text("Important message"),
            message: Text("Wear sunscreen"),
            dismissButton: .default(Text("Got it!"))
        )
        print("yes")
        if UserDefaults.standard.bool(forKey: "Test") ?? false {
            print("oh yeah baby")
            Alert(
                title: Text("Important message"),
                message: Text("Wear sunscreen"),
                dismissButton: .default(Text("Got it!"))
            )
            let something = UserDefaults.standard.bool(forKey: "Test")
            if let userDefaults = UserDefaults(suiteName: "group.settings-settings") {
                userDefaults.set(something, forKey: "Test")
        
            }
        }else{
            Alert(
                title: Text("Important message"),
                message: Text("Wear sunscreen"),
                dismissButton: .default(Text("Got it!"))
            )
        }
    }
}
