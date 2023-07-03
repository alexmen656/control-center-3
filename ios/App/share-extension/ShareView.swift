//
//  ShareView.swift
//  share-extension
//
//  Created by Alex Polan on 20/06/2023.
//

import UIKit

class ShareView: UIView {
    @IBOutlet private var titleLabel: UILabel!
    @IBOutlet private var actionButton: UIButton!

    // MARK: - Initialization

    override init(frame: CGRect) {
        super.init(frame: frame)
        commonInit()
    }

    required init?(coder aDecoder: NSCoder) {
        super.init(coder: aDecoder)
        commonInit()
    }

    private func commonInit() {
        let nib = UINib(nibName: "ShareView", bundle: nil)
        if let contentView = nib.instantiate(withOwner: self, options: nil).first as? UIView {
            contentView.frame = bounds
            contentView.autoresizingMask = [.flexibleWidth, .flexibleHeight]
            addSubview(contentView)
        }
    }

    // MARK: - Configuration

    func configure(withTitle title: String, buttonTitle: String) {
        titleLabel.text = title
        actionButton.setTitle(buttonTitle, for: .normal)
    }

    // MARK: - Actions

    @IBAction private func actionButtonTapped(_ sender: UIButton) {
        // Handle button tap action
    }
}
