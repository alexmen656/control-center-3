//
//  ImportExtension.swift
//  spotlight-extension
//
//  Created by Alex Polan on 20/06/2023.
//

import CoreSpotlight

class ImportExtension: CSImportExtension {
    
    override func update(_ attributes: CSSearchableItemAttributeSet, forFileAt: URL) throws {
        // Add attributes that describe the file at contentURL.
        // Throw an error with details on failure.
    }
    
}
