/**
 * Staff Profile vCard download.
 *
 * Contact data is read from the SSR Staff Profile already rendered in the
 * page. No API or Google Sheets request is made here.
 */
(() => {
    'use strict';

    const PROFILE_SELECTOR = '#staff-profile-container';
    const TRIGGER_SELECTOR = '.download-ics';

    const normalizeText = (value = '') => String(value)
        .replace(/\s+/g, ' ')
        .trim();

    const escapeVCardText = (value = '') => String(value)
        .replace(/\\/g, '\\\\')
        .replace(/\r?\n/g, '\\n')
        .replace(/;/g, '\\;')
        .replace(/,/g, '\\,');

    const sanitizeFilenamePart = (value = '') => String(value)
        .trim()
        .replace(/\s+/g, '_')
        .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '');

    const decodeProfileEmail = (profile) => {
        const emailElement = profile.querySelector('.staff-email.copy-email-to-clipboard');

        if (!emailElement) {
            return '';
        }

        const storedValue = String(emailElement.id || '').trim();

        if (!storedValue) {
            return '';
        }

        // Accept a plain email if the Directory markup changes in the future.
        if (storedValue.includes('@')) {
            return storedValue;
        }

        // Current Directory markup stores base64(email) + "c" in the ID.
        const encodedValue = storedValue.slice(0, -1);

        if (!encodedValue) {
            return '';
        }

        try {
            return atob(encodedValue);
        } catch (error) {
            console.warn('[CIMNE Staff Profile] Unable to decode profile email.', error);
            return '';
        }
    };

    const getPosition = (profile) => {
        const positionElements = profile.querySelectorAll(
            '.cimne-directory-profile-row--position .cimne-directory-profile-position'
        );

        if (positionElements.length) {
            return Array.from(positionElements)
                .map((element) => normalizeText(element.textContent))
                .filter(Boolean)
                .join(' / ');
        }

        return normalizeText(
            profile.querySelector('.cimne-directory-profile-row--position .column-right')?.textContent || ''
        );
    };

    const getContact = () => {
        const profile = document.querySelector(PROFILE_SELECTOR);

        if (!profile) {
            return null;
        }

        const address = normalizeText(
            profile.querySelector('.staff-profile-address')?.textContent || ''
        );

        const location = normalizeText(
            profile.querySelector('.staff-profile-branch')?.textContent || ''
        );

        return {
            fullName: normalizeText(
                profile.querySelector('.staff-profile-header-name')?.textContent || ''
            ),
            position: getPosition(profile),
            email: decodeProfileEmail(profile),
            phone: normalizeText(
                profile.querySelector('.staff-profile-header-tlf')?.textContent || ''
            ),
            address: address || location,
            organization: 'CIMNE'
        };
    };

    const buildVCard = (contact) => {
        const fullName = normalizeText(contact.fullName);
        const lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            `N:;${escapeVCardText(fullName)};;;`,
            `FN:${escapeVCardText(fullName)}`
        ];

        if (contact.organization) {
            lines.push(`ORG:${escapeVCardText(contact.organization)}`);
        }

        if (contact.position) {
            lines.push(`TITLE:${escapeVCardText(contact.position)}`);
        }

        if (contact.phone) {
            lines.push(`TEL;TYPE=WORK,VOICE:${escapeVCardText(contact.phone)}`);
        }

        if (contact.email) {
            lines.push(`EMAIL;TYPE=WORK,INTERNET:${escapeVCardText(contact.email)}`);
        }

        if (contact.address) {
            lines.push(`ADR;TYPE=WORK:;;${escapeVCardText(contact.address)};;;;`);
        }

        lines.push('END:VCARD');

        return lines.join('\r\n');
    };

    const downloadVCard = () => {
        const contact = getContact();

        if (!contact) {
            console.warn('[CIMNE Staff Profile] Staff profile data was not found.');
            return;
        }

        if (!contact.fullName && !contact.email && !contact.phone) {
            console.warn('[CIMNE Staff Profile] Staff profile data is empty.');
            return;
        }

        const blob = new Blob(
            [buildVCard(contact)],
            { type: 'text/vcard;charset=utf-8' }
        );

        const objectUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const filenameBase = sanitizeFilenamePart(contact.fullName) || 'cimne_contact';

        link.href = objectUrl;
        link.download = `${filenameBase}.vcf`;
        link.hidden = true;

        document.body.appendChild(link);
        link.click();
        link.remove();

        window.setTimeout(() => URL.revokeObjectURL(objectUrl), 0);
    };

    const handleDocumentClick = (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const trigger = target.closest(TRIGGER_SELECTOR);

        if (!trigger) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        downloadVCard();
    };

    document.addEventListener('click', handleDocumentClick);

    // Temporary compatibility until the legacy vCard caller is removed.
    window.generarVCard = downloadVCard;
})();
