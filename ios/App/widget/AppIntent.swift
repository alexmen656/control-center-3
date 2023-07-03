//
//  AppIntent.swift
//  widget
//
//  Created by Alex Polan on 17/06/2023.
//"https://alex.polan.sk/control-center/refresh_rates.php"

import WidgetKit
import AppIntents
import Foundation



enum RefreshInterval: String, AppEnum {
    case hourly, daily, weekly, monthly


    static var typeDisplayRepresentation: TypeDisplayRepresentation = "Refresh Interval"
    static var caseDisplayRepresentations: [RefreshInterval : DisplayRepresentation] = [
        .hourly: "Every Hour",
        .daily: "Every Day",
        .weekly: "Every Week",
        .monthly: "Every Month",
    ]
    
    


    

    // Beispielaufruf der Funktion
  

   
}


struct ConfigurationAppIntent: WidgetConfigurationIntent {
    static var title: LocalizedStringResource = "Configuration"
    static var description = IntentDescription("This is an example widget.")

    // An example configurable parameter.
    @Parameter(title: "Data", default: "Sells")
    var favoriteEmoji: String

    @Parameter(title: "Test", default: "Sells")
    var selectedOption: String
    
    @Parameter(title: "Refresh", default: .daily)
        var interval: RefreshInterval
}
