// Admin timetable slot grid: add/remove rows, paste rows from a spreadsheet,
// copy slots from another event's timetable. Everything is saved in one submit.

const WEEKDAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

function parseTime(text) {
    const value = (text || '').trim().toLowerCase();
    if (value === '') {
        return '';
    }
    if (value === 'noon' || value === 'midday') {
        return '12:00';
    }
    if (value === 'midnight') {
        return '00:00';
    }
    // "16:00", "16.00", "4pm", "4:30 PM", "4:00:00 PM" (Google Sheets)
    const match = value.match(/(\d{1,2})(?:[:.h](\d{2}))?/);
    if (!match) {
        return '';
    }
    const suffix = (value.match(/\b(am|pm)\b|\d(am|pm)\b/) || [])[0] || '';
    let hours = parseInt(match[1], 10);
    const minutes = match[2] ? parseInt(match[2], 10) : 0;
    if (suffix.endsWith('pm') && hours < 12) {
        hours += 12;
    }
    if (suffix.endsWith('am') && hours === 12) {
        hours = 0;
    }
    if (hours > 23 || minutes > 59) {
        return '';
    }
    return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
}

// Turn "Friday", "Fri", "2026-11-14" or "14/11/2026" into one of the grid's day values
function parseDay(text, dayValues) {
    const value = (text || '').trim().toLowerCase();
    if (value === '') {
        return '';
    }
    let match = value.match(/(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
        return match[0];
    }
    match = value.match(/(\d{1,2})[/.](\d{1,2})[/.](\d{2,4})/);
    if (match) {
        const year = match[3].length === 2 ? '20' + match[3] : match[3];
        return year + '-' + match[2].padStart(2, '0') + '-' + match[1].padStart(2, '0');
    }
    const weekday = WEEKDAYS.findIndex((name) => value.startsWith(name));
    if (weekday === -1) {
        return '';
    }
    return dayValues.find((day) => new Date(day + 'T12:00:00').getDay() === weekday) || '';
}

function splitRows(text) {
    return text
        .replace(/\r/g, '')
        .split('\n')
        .filter((line) => line.trim() !== '')
        .map((line) => (line.includes('\t') ? line.split('\t') : line.split(',')).map((cell) => cell.trim()));
}

const HEADER_PATTERNS = {
    day: /^(day|date)$/,
    time: /^(time|start|start time)$/,
    name: /^(name|event|title|game|activity|slot)$/,
    desc: /^(description|desc|host|details|notes)$/,
};

function findColumn(headers, key) {
    // Earlier patterns win: for the description, "description" beats "host" beats "notes"
    const options = HEADER_PATTERNS[key].source.replace(/^\^\(|\)\$$/g, '').split('|');
    for (const option of options) {
        const index = headers.findIndex((header) => header.toLowerCase() === option);
        if (index !== -1) {
            return index;
        }
    }
    return -1;
}

export function initTimetableGrid() {
    const grid = document.querySelector('[data-slot-grid]');
    if (!grid) {
        return;
    }
    const form = grid.querySelector('#slotGridForm');
    const tbody = grid.querySelector('[data-slot-rows]');
    const template = grid.querySelector('#slotRowTemplate');
    const notice = grid.querySelector('[data-slot-notice]');
    let dirty = false;
    let nextIndex = tbody.querySelectorAll('[data-slot-row]').length + 1000;

    const dayValues = () => Array.from(template.content.querySelectorAll('select option')).map((o) => o.value);

    const showNotice = (text) => {
        notice.textContent = text;
        notice.classList.remove('d-none');
    };

    const markDirty = () => {
        dirty = true;
    };

    const isBlankNewRow = (row) =>
        !row.dataset.slotId &&
        row.querySelector('[name$="[name]"]').value.trim() === '' &&
        row.querySelector('[name$="[desc]"]').value.trim() === '';

    const addRow = (values = {}) => {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('[data-slot-row]');
        row.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replace('__INDEX__', String(nextIndex));
        });
        nextIndex++;
        const day = row.querySelector('[name$="[day]"]');
        if (values.day) {
            if (!Array.from(day.options).some((option) => option.value === values.day)) {
                day.add(new Option(values.day, values.day));
            }
            day.value = values.day;
        } else {
            // A new row starts on the same day as the row above it
            const last = tbody.querySelector('[data-slot-row]:last-child [name$="[day]"]');
            if (last) {
                day.value = last.value;
            }
        }
        if (values.dayUnknown) {
            day.classList.add('is-invalid');
        }
        row.querySelector('[name$="[time]"]').value = values.time || '';
        if (values.time === '' && values.name) {
            row.querySelector('[name$="[time]"]').classList.add('is-invalid');
        }
        row.querySelector('[name$="[name]"]').value = values.name || '';
        row.querySelector('[name$="[desc]"]').value = values.desc || '';
        tbody.appendChild(row);
        return row;
    };

    const setRemoved = (row, removed) => {
        row.querySelector('[data-slot-remove-input]').value = removed ? '1' : '0';
        row.classList.toggle('slot-removed', removed);
        const button = row.querySelector('[data-slot-remove]');
        const label = removed ? 'Keep this slot' : 'Remove this slot';
        button.title = label;
        button.setAttribute('aria-label', label);
        button.querySelector('i').className = 'fa ' + (removed ? 'fa-rotate-left' : 'fa-trash') + ' fa-fw';
    };

    // replaceCheckbox: the panel's "Remove the slots already in this timetable" box
    const addRows = (rows, replaceCheckbox) => {
        const replace = replaceCheckbox && replaceCheckbox.checked;
        tbody.querySelectorAll('[data-slot-row]').forEach((row) => {
            if (row.dataset.slotId) {
                if (replace) {
                    setRemoved(row, true);
                }
            } else if (replace || isBlankNewRow(row)) {
                row.remove();
            }
        });
        rows.forEach((values) => addRow(values));
        markDirty();
    };

    grid.querySelector('[data-slot-add]').addEventListener('click', () => {
        const row = addRow();
        markDirty();
        row.querySelector('[name$="[name]"]').focus();
    });

    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-slot-remove]');
        if (!button) {
            return;
        }
        const row = button.closest('[data-slot-row]');
        markDirty();
        if (!row.dataset.slotId) {
            row.remove();
            return;
        }
        setRemoved(row, row.querySelector('[data-slot-remove-input]').value !== '1');
    });

    tbody.addEventListener('input', (event) => {
        event.target.classList.remove('is-invalid');
        markDirty();
    });
    tbody.addEventListener('change', markDirty);

    // Paste from a spreadsheet
    const pasteText = grid.querySelector('#slotPasteText');
    const pasteColumns = grid.querySelector('[data-paste-columns]');
    const pasteName = grid.querySelector('#slotPasteName');
    const pasteDesc = grid.querySelector('#slotPasteDesc');
    const pasteSummary = grid.querySelector('[data-paste-summary]');
    const pasteAdd = grid.querySelector('[data-paste-add]');
    let parsed = null;

    const fillSelect = (select, labels, selected, allowNone) => {
        select.innerHTML = '';
        if (allowNone) {
            select.add(new Option('(none)', '-1'));
        }
        labels.forEach((label, index) => select.add(new Option(label, String(index))));
        select.value = String(selected);
    };

    const parsePaste = () => {
        const rows = splitRows(pasteText.value);
        parsed = null;
        if (rows.length === 0) {
            pasteColumns.classList.add('d-none');
            pasteAdd.disabled = true;
            return;
        }
        const width = Math.max(...rows.map((row) => row.length));
        const first = rows[0].map((cell) => cell.toLowerCase());
        const hasHeader = first.some((cell) => HEADER_PATTERNS.time.test(cell) || HEADER_PATTERNS.day.test(cell));
        let columns = { day: 0, time: 1, name: 2, desc: width > 3 ? 3 : -1 };
        let labels;
        if (hasHeader) {
            const headers = rows[0];
            columns = {
                day: findColumn(headers, 'day'),
                time: findColumn(headers, 'time'),
                name: findColumn(headers, 'name'),
                desc: findColumn(headers, 'desc'),
            };
            if (columns.name === -1) {
                columns.name = headers.findIndex((_, index) => index !== columns.day && index !== columns.time);
            }
            labels = Array.from({ length: width }, (_, index) => headers[index] || 'Column ' + (index + 1));
        } else {
            labels = Array.from({ length: width }, (_, index) => {
                const sample = rows[0][index] || '';
                return 'Column ' + (index + 1) + (sample ? ': ' + sample.slice(0, 24) : '');
            });
        }
        parsed = { rows: hasHeader ? rows.slice(1) : rows, columns };
        fillSelect(pasteName, labels, columns.name, false);
        fillSelect(pasteDesc, labels, columns.desc, true);
        pasteSummary.textContent = parsed.rows.length + (parsed.rows.length === 1 ? ' row' : ' rows') + ' found';
        pasteColumns.classList.remove('d-none');
        pasteAdd.disabled = parsed.rows.length === 0;
    };

    if (pasteText) {
        pasteText.addEventListener('input', parsePaste);
        pasteAdd.addEventListener('click', () => {
            if (!parsed) {
                return;
            }
            const days = dayValues();
            const nameColumn = parseInt(pasteName.value, 10);
            const descColumn = parseInt(pasteDesc.value, 10);
            const { day: dayColumn, time: timeColumn } = parsed.columns;
            let currentDay = '';
            let unknownDays = 0;
            let missingTimes = 0;
            const rows = parsed.rows.map((cells) => {
                const dayCell = dayColumn >= 0 ? cells[dayColumn] || '' : '';
                const day = parseDay(dayCell, days);
                if (day) {
                    currentDay = day;
                }
                // "Fri 16:00" in one cell works too
                const time = parseTime(timeColumn >= 0 && cells[timeColumn] ? cells[timeColumn] : dayCell.replace(/^\D+/, ''));
                const dayUnknown = !currentDay || (dayCell.trim() !== '' && !day);
                if (dayUnknown) {
                    unknownDays++;
                }
                if (!time) {
                    missingTimes++;
                }
                return {
                    day: currentDay || days[0],
                    dayUnknown,
                    time,
                    name: cells[nameColumn] || '',
                    desc: descColumn >= 0 ? cells[descColumn] || '' : '',
                };
            });
            addRows(rows, grid.querySelector('#slotPasteReplace'));
            let message = 'Added ' + rows.length + (rows.length === 1 ? ' row' : ' rows') + '. Check them, then press Save timetable.';
            if (unknownDays || missingTimes) {
                const problems = [];
                if (unknownDays) {
                    problems.push(unknownDays + ' with a day');
                }
                if (missingTimes) {
                    problems.push(missingTimes + ' with a time');
                }
                message += ' Couldn\'t read ' + problems.join(' and ') + ': those are outlined in red.';
            }
            showNotice(message);
            pasteText.value = '';
            parsePaste();
        });
    }

    // Copy from another event
    const copyData = grid.querySelector('#slotCopySources');
    const copyButton = grid.querySelector('[data-copy-add]');
    if (copyData && copyButton) {
        const sources = JSON.parse(copyData.textContent);
        copyButton.addEventListener('click', () => {
            const id = grid.querySelector('#slotCopySource').value;
            const source = sources.find((item) => String(item.id) === id);
            if (!source) {
                return;
            }
            addRows(source.slots, grid.querySelector('#slotCopyReplace'));
            showNotice('Added ' + source.slots.length + ' slots from ' + source.label.replace(/ \(\d+ slots\)$/, '') + '. Check them, then press Save timetable.');
        });
    }

    form.addEventListener('submit', () => {
        dirty = false;
    });
    window.addEventListener('beforeunload', (event) => {
        if (dirty) {
            event.preventDefault();
        }
    });
}
