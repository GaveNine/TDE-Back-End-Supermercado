/* Carrossel de banners + botões de quantidade (− / +) */
(function () {
    // ---------- Carrossel ----------
    var car = document.getElementById('carrossel');
    if (car) {
        var slides = car.querySelectorAll('.slide');
        var dotsBox = car.querySelector('.pontos');
        var atual = 0, timer = null;

        slides.forEach(function (_, i) {
            var b = document.createElement('button');
            b.type = 'button';
            b.setAttribute('aria-label', 'Banner ' + (i + 1));
            b.addEventListener('click', function () { ir(i); reiniciar(); });
            dotsBox.appendChild(b);
        });

        function ir(i) {
            atual = (i + slides.length) % slides.length;
            slides.forEach(function (s, k) { s.classList.toggle('ativo', k === atual); });
            dotsBox.querySelectorAll('button').forEach(function (d, k) { d.classList.toggle('ativo', k === atual); });
        }
        function reiniciar() { clearInterval(timer); timer = setInterval(function () { ir(atual + 1); }, 5500); }

        car.querySelector('.seta-ant').addEventListener('click', function () { ir(atual - 1); reiniciar(); });
        car.querySelector('.seta-prox').addEventListener('click', function () { ir(atual + 1); reiniciar(); });
        ir(0); reiniciar();
    }

    // ---------- Stepper de quantidade ----------
    document.querySelectorAll('.qtd').forEach(function (box) {
        var input = box.querySelector('input');
        box.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var min = parseInt(input.min || '1', 10);
                var max = parseInt(input.max || '9999', 10);
                var v = parseInt(input.value || '1', 10) + parseInt(btn.dataset.passo, 10);
                input.value = Math.min(max, Math.max(min, v));
            });
        });
    });
})();
