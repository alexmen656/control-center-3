//
//  ExtensionView.swift
//  watchkitextensionExtension
//
//  Created by Alex Polan on 15/02/2023.
//

import WidgetKit
import SwiftUI
import Intents

struct watchkitextensionEntryView : View {
let defaults = UserDefaults(suiteName: "group.widgetName")
    @Environment(\.widgetFamily) var family


       @ViewBuilder
       var body: some View {
           switch family {
           case .accessoryInline:
               
               Text("\(defaults?.string(forKey: "firstName") ?? "Not")  \(defaults?.string(forKey: "lastName") ?? "Avaible")")
           case .accessoryRectangular:
               Text("Accessory Rectengulart")
           case .accessoryCircular:
            /*   if let image = UIImage(named: "widgetImage") {
                  // Text("image found")
                   Image(uiImage: image)
                                 //  .resizable()
                                   .scaledToFit()
                                   // .clipShape(Circle())
                           } else {
                               Text("Bild nicht gefunden")
                           }*/
              // Circle()
               ZStack {
                 //  Circle()
                       //.fill(Color(.darkGray))
                   Text("CC")
                                .font(.custom("Chalkduster", size: 24))
                                .foregroundColor(.red)
                                
               }
         
           case .systemSmall:
               Text("System Small")
           case .accessoryCorner:
               ZStack {
                   Circle()
                       .fill(Color(red: 29/255, green: 29/255, blue: 29/255))
                   Text("CC")
                                .font(.custom("Chalkduster", size: 18))
                                .foregroundColor(.red)
                                
               }
                
               
               
           default:
               Text("Default")
           }
       }

    }

struct watchkitextension_Previews: PreviewProvider {
    static var previews: some View {
        watchkitextensionEntryView()
    }
}
