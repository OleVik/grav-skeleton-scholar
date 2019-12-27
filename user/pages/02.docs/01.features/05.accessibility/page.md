---
title: Accessibility
---

The theme is written with accessibility in mind from the ground up; it uses semantic [landmarks](landmarks), ARIA-roles and -properties, as well as keyboard-navigation and event handlers for interaction. This ensures that the website can be navigated using a keyboard with ease, and also through screen readers. The approach of progressive enhancement is aimed at making the theme apt for a wide audience.

## Supported browsers

Both the CSS and JS used in the theme are compiled against [Browserslist](https://github.com/browserslist/browserslist#best-practices) "default" [query](https://browserl.ist/?q=defaults). Simply put, this includes browsers that have more than 0.5% market coverage, including their last two versions, Firefox Extended Support Release, and that are not considered dead - that is, not receiving updates from its vendor or author anymore. At the end of December 2019, this means a browser-coverage of 91.34%.

This also means that certain infamous browsers, like Internet Explorer version 5.5-10 is not supported. Nor is its newest version, 11, particularly paid attention to. The main focus is supporting ["Evergreen browsers"](https://learn-the-web.algonquindesign.ca/topics/browser-testing/#evergreen-browsers), which update themselves regularly and thus support and follow modern standards, specifications, and features.

## Testing

The end-to-end testing runner [Cypress](https://www.cypress.io/) is used for testing Components against [Web Content Accessibility Guidelines (WCAG)](https://www.deque.com/wcag/) level A, [Section 508](https://www.deque.com/section-508-compliance/) of the Rehabilitation Act of 1973, and Best Practices in [Axe Core](https://github.com/dequelabs/axe-core).

This automates testing of ARIA compliance, necessary contrast for all Styles, that elements can be interacted with, and that tabbing behavior follows the expected logic. All responsive breakpoints, landmarks, and Styles are also tested and inspected for correct behavior.
