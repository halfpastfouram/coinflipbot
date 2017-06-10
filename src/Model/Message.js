/*jshint esversion: 6 */
/*jshint sub:true*/
'use strict';

class Message {
    constructor() {
        this.bodyText = '';
        this.footerText = '';
        this.footerSeparator = "\n\n---\n\n";
    }

    get body() {
        return this.bodyText;
    }

    set body(value) {
        this.bodyText = value;
    }

    get footer() {
        return this.footerText;
    }

    set footer(value) {
        this.footerText = value;
    }

    /**
     * Generate a string containing the body and footer
     * @returns {string}
     */
    toString() {
        return `${this.bodyText}${this.footerSeparator}${this.footerText}`;
    }
}

module.exports = Message;