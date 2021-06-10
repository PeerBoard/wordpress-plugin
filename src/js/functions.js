/**
 * Vanilla js serialize array function
 * @param {*} form 
 */
var serializeArray = function (form) {
    var arr = [];
    Array.prototype.slice.call(form.elements).forEach(function (field) {
        if (!field.name || field.disabled || ['file', 'reset', 'submit', 'button'].indexOf(field.type) > -1) return;
        if (field.type === 'select-multiple') {
            Array.prototype.slice.call(field.options).forEach(function (option) {
                if (!option.selected) return;
                arr.push({
                    name: field.name,
                    value: option.value
                });
            });
            return;
        }
        if (['checkbox', 'radio'].indexOf(field.type) >-1 && !field.checked) return;
        arr.push({
            name: field.name,
            value: field.value
        });
    });
    return arr;
};