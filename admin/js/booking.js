document.addEventListener('DOMContentLoaded', () => {
    // маска телефона и дат (jquery.inputmask уже подключён в шаблоне)
    if (window.jQuery && jQuery.fn.inputmask) {
        jQuery('#bookingForm input[name="phone"]').inputmask('+7(999)999-99-99');
        jQuery('#check_in, #check_out').inputmask('99.99.9999');
    }

    // Запрет дат: заезд — не раньше сегодня, выезд — позже заезда,
    // и подсветка занятых периодов (на сервере всё равно перепроверяется).
    const busy = window.BUSY_PERIODS || [];
    const inEl  = document.getElementById('check_in');
    const outEl = document.getElementById('check_out');

    const parse = s => {
        const m = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(s || '');
        return m ? new Date(+m[3], +m[2]-1, +m[1]) : null;
    };
    const fmt = d => String(d.getDate()).padStart(2,'0') + '.' +
                     String(d.getMonth()+1).padStart(2,'0') + '.' + d.getFullYear();

    // минимальная дата заезда — сегодня
    const today = new Date(); today.setHours(0,0,0,0);
    inEl?.addEventListener('change', () => {
        const d = parse(inEl.value);
        if (d && d < today) {
            alert('Дата заезда не может быть раньше сегодняшней.');
            inEl.value = '';
            return;
        }
        if (d && !outEl.value) {
            const next = new Date(d); next.setDate(next.getDate()+1);
            outEl.value = fmt(next);
        }
        checkBusy();
    });
    outEl?.addEventListener('change', () => {
        const a = parse(inEl.value), b = parse(outEl.value);
        if (a && b && b <= a) {
            alert('Дата выезда должна быть позже даты заезда.');
            outEl.value = '';
            return;
        }
        checkBusy();
    });

    function checkBusy() {
        const a = parse(inEl.value), b = parse(outEl.value);
        if (!a || !b) return;
        const conflict = busy.some(p => {
            const pin = new Date(p.check_in + 'T00:00:00');
            const pout = new Date(p.check_out + 'T00:00:00');
            return a < pout && b > pin;
        });
        if (conflict) {
            alert('На выбранные даты номер уже забронирован. Выберите другие даты.');
        }
    }
});