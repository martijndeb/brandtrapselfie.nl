var btsapp = {
    filterState: false,
    init: function(ev) {
        var elements = document.querySelectorAll("input[data-filter]");
        Array.prototype.forEach.call(elements, function(el, i){
            el.addEventListener("change", btsapp.applyFilter);
        });

        var filterLine = document.querySelector(".header table tr:first-child");
        filterLine.addEventListener("click", btsapp.toggleFilter);
    },

    toggleFilter: function(ev) {
        if (ev && ev.preventDefault) {
            ev.preventDefault();
        }

        btsapp.filterState = !btsapp.filterState;
        var elements = document.querySelectorAll(".header table tr:not(:first-child)");
        Array.prototype.forEach.call(elements, function(el, i){
            el.style.display = btsapp.filterState ? 'table-row' : 'none';
        });
    },

    applyFilter: function(ev) {
        var t = ev.target;
        var c = t.checked;
        var f = t.getAttribute("data-filter");
        var v = t.getAttribute("data-value");
        var s = "img[data-" + f + "=" + v + "]";
        var m = document.querySelectorAll(s);

        Array.prototype.forEach.call(m, function(el, i){
            var fp = el.getAttribute("data-dagdeel");
            var fd = el.getAttribute("data-dag");
            var ip = document.querySelector("input[data-filter=dagdeel][data-value=" + fp + "]");
            var id = document.querySelector("input[data-filter=dag][data-value=" + fd + "]");

            if (ip.checked && id.checked) {
                el.parentElement.style.display = '';
            } else {
                el.parentElement.style.display = 'none';
            }
        });
    }
}

if (document.readyState != 'loading'){
    btsapp.init();
} else {
    document.addEventListener('DOMContentLoaded', btsapp.init);
}
