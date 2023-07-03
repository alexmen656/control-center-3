//
//  ShareViewController.swift
//  share-extension
//
//  Created by Alex Polan on 20/06/2023.
//

import UIKit

class ShareViewController: UIViewController {
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Anpassen der Hintergrundfarbe
        self.view.backgroundColor = UIColor.lightGray
        
        // Hinzufügen der Elemente direkt zur View
        let titleLabel = UILabel()
        titleLabel.text = "Custom Share"
        titleLabel.font = UIFont.boldSystemFont(ofSize: 20)
        titleLabel.textColor = UIColor.white
        titleLabel.textAlignment = .center
        titleLabel.translatesAutoresizingMaskIntoConstraints = false
        self.view.addSubview(titleLabel)
        
        let closeButton = UIButton(type: .system)
        closeButton.setTitle("Close", for: .normal)
        closeButton.titleLabel?.font = UIFont.systemFont(ofSize: 16)
        closeButton.tintColor = UIColor.white
        closeButton.addTarget(self, action: #selector(closeButtonTapped), for: .touchUpInside)
        closeButton.translatesAutoresizingMaskIntoConstraints = false
        self.view.addSubview(closeButton)
        
        // Constraints für die Elemente festlegen
        NSLayoutConstraint.activate([
            titleLabel.topAnchor.constraint(equalTo: self.view.topAnchor, constant: 100),
            titleLabel.leadingAnchor.constraint(equalTo: self.view.leadingAnchor),
            titleLabel.trailingAnchor.constraint(equalTo: self.view.trailingAnchor),
            
            closeButton.topAnchor.constraint(equalTo: titleLabel.bottomAnchor, constant: 20),
            closeButton.centerXAnchor.constraint(equalTo: self.view.centerXAnchor)
        ])
    }
    
    @objc func closeButtonTapped() {
        // Aktion, wenn die Close-Schaltfläche gedrückt wird
        self.extensionContext?.cancelRequest(withError: NSError(domain: "com.example", code: 0, userInfo: nil))
    }
    
}
