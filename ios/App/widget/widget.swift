//
//  widget.swift
//  widget
//
//  Created by Alex Polan on 17/06/2023.
//

import WidgetKit
import SwiftUI
import Charts

struct ServerResponse: Codable {
    let message: String
}

struct Provider: AppIntentTimelineProvider {
    func placeholder(in context: Context) -> SimpleEntry {
        SimpleEntry(date: Date(), configuration: ConfigurationAppIntent(), serverResponse: nil)
    }

    func snapshot(for configuration: ConfigurationAppIntent, in context: Context) async -> SimpleEntry {
        SimpleEntry(date: Date(), configuration: configuration, serverResponse: nil)
    }
    
    func timeline(for configuration: ConfigurationAppIntent, in context: Context) async -> Timeline<SimpleEntry> {
        let currentDate = Date()
        var entries: [SimpleEntry] = []

        // Fetch data from the server
        if let serverResponse = await fetchDataFromServer() {
            for hourOffset in 0 ..< 15 {
                let entryDate = Calendar.current.date(byAdding: .hour, value: hourOffset, to: currentDate)!
                let entry = SimpleEntry(date: entryDate, configuration: configuration, serverResponse: serverResponse)
                entries.append(entry)
            }
        } else {
            let entryDate = Calendar.current.date(byAdding: .hour, value: 0, to: currentDate)!
            let entry = SimpleEntry(date: entryDate, configuration: configuration, serverResponse: nil)
            entries.append(entry)
        }

        return Timeline(entries: entries, policy: .atEnd)
    }
    
    func fetchDataFromServer() async -> ServerResponse? {
        guard let url = URL(string: "https://alex.polan.sk/control-center/widget.php") else {
            return nil
        }

        do {
            let (data, _) = try await URLSession.shared.data(from: url)
            let decoder = JSONDecoder()
            let serverResponse = try decoder.decode(ServerResponse.self, from: data)
            return serverResponse
        } catch {
            print("Error: \(error)")
            return nil
        }
    }
}

struct SimpleEntry: TimelineEntry {
    let date: Date
    let configuration: ConfigurationAppIntent
    let serverResponse: ServerResponse?
}

struct widgetEntryView : View {
    var entry: Provider.Entry
    @Environment(\.widgetFamily) var widgetFamily

    var serverResponseText: String {
        if let response = entry.serverResponse {
            return response.message
        } else {
            return "Error: Failed to fetch data"
        }
    }

    var body: some View {
        VStack {
            switch widgetFamily {
            case .systemSmall:
                Text("Time:")
                Text(entry.date, style: .time)
                Text(serverResponseText)
                Text(entry.configuration.selectedOption)
            case .accessoryInline:
                Text(serverResponseText)
            case .accessoryRectangular:
                Text(serverResponseText)
            case .accessoryCircular:
                Image("icon").frame(width: 20, height: 20)
            default:
                Chart {
                    BarMark(x: .value("Day", "Jun 1"), y: .value("Value", 50))
                    BarMark(x: .value("Day", "Jun 2"), y: .value("Value", 30))
                        .foregroundStyle(.blue)
                    BarMark(x: .value("Day", "Jun 3"), y: .value("Value", 20))
                        .foregroundStyle(.teal)
                }
            }
        }
        .containerBackground(.fill.tertiary, for: .widget)
    }
}

struct widget: Widget {
    let kind: String = "widget"

    var body: some WidgetConfiguration {
        AppIntentConfiguration(kind: kind, intent: ConfigurationAppIntent.self, provider: Provider()) { entry in
            widgetEntryView(entry: entry)
        }
        .configurationDisplayName("Test 1")
        .description("Test2")
        .supportedFamilies([.systemSmall, .systemMedium, .systemLarge, .accessoryInline, .accessoryCircular, .accessoryRectangular])
    }
}
