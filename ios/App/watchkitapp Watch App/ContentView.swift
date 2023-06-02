//
//  ContentView.swift
//  watchkiappp Watch App
//
//  Created by Alex Polan on 11/02/2023.
//

import SwiftUI

struct LoginView: View {
    @State private var email: String = ""
    @State private var password: String = ""
    @State private var showAlert = false
    @State private var alertMessage = ""
    @State private var token: String? = nil
    var body: some View {
        VStack {
            if self.token != nil {
                TokenView()
            } else {
                HStack {
                    Text("Login")
                        .font(.title)
                        .foregroundColor(.red)
                }
               
                
                TextField("Email", text: $email)
                  //  .padding()
                    .background(Color(UIColor.darkGray))
                    .foregroundColor(.red)
                    .cornerRadius(5.0)
                
                SecureField("Password", text: $password)
                   // .padding()
                    .background(Color(UIColor.darkGray))
                    .foregroundColor(.red)
                    .cornerRadius(5.0)
                
                Button(action: {
                    self.performLogin()
                }) {
                    Text("Login")
                        .font(.headline)
                        .foregroundColor(.white)
                    
                }.background(Color.red)
                 .cornerRadius(5.0)
                 .padding()
            }
        }.padding()
            .alert(isPresented: $showAlert) {
                Alert(title: Text("Error"), message: Text(self.alertMessage), dismissButton: .default(Text("OK")))
            }
    }
    
    private func performLogin() {
        let url = URL(string: "https://alex.polan.sk/api/blog/login.php")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/x-www-form-urlencoded", forHTTPHeaderField: "Content-Type")
        let parameters = ["email": email, "password": password]
        let postData = parameters.compactMap({ (key, value) -> String in
            return "\(key)=\(value)"
        }).joined(separator: "&").data(using: .utf8)
        request.httpBody = postData
        
        URLSession.shared.dataTask(with: request) { (data, response, error) in
            if let error = error {
                DispatchQueue.main.async {
                    self.alertMessage = error.localizedDescription
                    self.showAlert = true
                }
                return
            }
            
            if let data = data {
                let responseString = String(data: data, encoding: .utf8)
                print(responseString)
                if let responseData = responseString?.data(using: .utf8) {
                    
                    do {
                        let decodedResponse = try JSONDecoder().decode([String: String].self, from: responseData)
                        let token = decodedResponse["token"]
                        let defaults = UserDefaults(suiteName: "group.widgetName")
                        defaults?.set(token, forKey: "token")
                        self.token = token
                    } catch {
                        print(error)
                    }
                }

                print("Token: \(self.token)")
                
            }
        }.resume()
    }
}

struct TokenView: View {
    @State private var firstName = ""
    @State private var lastName = ""
    @State private var sales = 0
    let defaults = UserDefaults(suiteName: "group.widgetName")

    var body: some View {
        VStack {
            Text("Dashboard")
                .font(.title)
                .foregroundColor(.red)
            
                  Card {
                      Text("Token: \(defaults?.string(forKey: "token") ?? "No Token Found")")
                          .font(.headline)
                          .foregroundColor(.red)
                  }
                  
                  Card {
                      Text("First Name: \(firstName)")
                          .font(.headline)
                          .foregroundColor(.red)
                  }
                  
                  Card {
                      Text("Last Name: \(lastName)")
                          .font(.headline)
                          .foregroundColor(.red)
                  }
        }
        .padding()
        .onAppear(perform: fetchData)
    }
    
    private func fetchData() {
        guard let token = defaults?.string(forKey: "token") else { return }
        let headers = ["Authorization": "\(token)"]
        
        let url = URL(string: "https://alex.polan.sk/api/blog/user.php")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.allHTTPHeaderFields = headers
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            if let error = error {
                print("Error fetching data: \(error)")
                return
            }
            
            if let data = data {
                do {
                        print(data)
                    if let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],

                       let firstName = json["firstName"] as? String,
                       let lastName = json["lastName"] as? String {
                        self.firstName = firstName
                        self.lastName = lastName
                        self.sales = sales
                    }
                    defaults?.set(firstName, forKey: "firstName")
                    defaults?.set(lastName, forKey: "lastName")

                } catch {
                    print("Error decoding data: \(error)")
                }
            }

        }.resume()
    }
}


struct ContentView: View {
    var body: some View {
        Group {
            let defaults = UserDefaults(suiteName: "group.widgetName")
            if defaults?.string(forKey: "token") != nil {
                TokenView()
            } else {
                LoginView()
            }
        }
    }
}

struct Card<Content: View>: View {
    let content: Content
    
    init(@ViewBuilder content: () -> Content) {
        self.content = content()
    }
    
    var body: some View {
        content
            .padding()
            .background(Color.gray.opacity(0.1))
            .cornerRadius(10)
    }
}

