/**
 * Botones de descarga de las fotos compradas.
 *
 * En iPhone y iPad, Safari guarda las descargas en la app Archivos. Para que la foto
 * llegue a Fotos, ahí el botón baja el archivo y abre el menú de compartir de iOS,
 * que tiene la opción "Guardar imagen". En el resto de los dispositivos el botón es
 * un link de descarga normal.
 *
 * Safari solo deja abrir el menú de compartir si pasó poco tiempo desde el toque.
 * Si la descarga tarda y lo bloquea, el archivo ya queda bajado y el botón pide un
 * segundo toque, que abre el menú al instante.
 */
(function () {
  'use strict';

  var esIOS = /iPhone|iPad|iPod/.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

  var usarMenuCompartir = esIOS && typeof navigator.share === 'function' && typeof navigator.canShare === 'function' && typeof File === 'function';

  // Archivos ya bajados, por URL de descarga
  var archivos = {};

  function bajarArchivo(boton) {
    var url = boton.href;
    if (archivos[url]) {
      return Promise.resolve(archivos[url]);
    }
    return fetch(url, { credentials: 'same-origin' })
      .then(function (respuesta) {
        if (!respuesta.ok) {
          throw new Error('Error ' + respuesta.status + ' al bajar ' + url);
        }
        return respuesta.blob();
      })
      .then(function (blob) {
        var tipo = blob.type && blob.type.indexOf('image/') === 0 ? blob.type : 'image/jpeg';
        var archivo = new File([blob], boton.dataset.gambaArchivo || 'foto-gamba.jpg', { type: tipo });
        archivos[url] = archivo;
        return archivo;
      });
  }

  function mostrarMensaje(seccion, texto) {
    var mensaje = seccion.querySelector('.gamba-descargas__mensaje');
    if (!mensaje) {
      return;
    }
    mensaje.textContent = texto;
    mensaje.hidden = !texto;
  }

  /**
   * Baja las fotos de los botones y abre el menú de compartir.
   * Si no se puede compartir, sigue con la descarga normal (alDescargar).
   */
  function compartir(disparador, botones, textoOriginal, alDescargar) {
    var seccion = disparador.closest('.gamba-descargas');

    if (disparador.dataset.gambaEstado === 'cargando') {
      return;
    }
    disparador.dataset.gambaEstado = 'cargando';
    disparador.classList.add('is-cargando');
    disparador.textContent = 'Preparando…';
    mostrarMensaje(seccion, '');

    function restaurar(texto) {
      disparador.dataset.gambaEstado = '';
      disparador.classList.remove('is-cargando');
      disparador.textContent = texto || textoOriginal;
    }

    Promise.all(botones.map(bajarArchivo))
      .then(function (lista) {
        if (!navigator.canShare({ files: lista })) {
          restaurar();
          alDescargar();
          return null;
        }
        return navigator.share({ files: lista }).then(function () {
          restaurar();
        });
      })
      .catch(function (error) {
        if (error && error.name === 'AbortError') {
          // El usuario cerró el menú sin guardar
          restaurar();
        } else if (error && error.name === 'NotAllowedError') {
          // Safari bloqueó el menú porque la descarga tardó: las fotos ya están bajadas
          restaurar('Tocá para guardar');
        } else {
          restaurar();
          mostrarMensaje(seccion, 'No pudimos preparar la foto. Probá de nuevo o descargala desde una computadora.');
          if (window.console) {
            console.error('[Gamba] Error al compartir la foto', error);
          }
        }
      });
  }

  function descargarUnaPorUna(botones) {
    botones.forEach(function (boton, i) {
      setTimeout(function () {
        var link = document.createElement('a');
        link.href = boton.href;
        link.download = boton.dataset.gambaArchivo || '';
        document.body.appendChild(link);
        link.click();
        link.remove();
      }, i * 600);
    });
  }

  document.querySelectorAll('.gamba-descargas').forEach(function (seccion) {
    var botones = Array.prototype.slice.call(seccion.querySelectorAll('.gamba-descargas__boton'));
    var todas = seccion.querySelector('.gamba-descargas__todas');

    if (usarMenuCompartir) {
      botones.forEach(function (boton) {
        var textoOriginal = boton.textContent;
        boton.addEventListener('click', function (evento) {
          evento.preventDefault();
          compartir(boton, [boton], textoOriginal, function () {
            window.location.href = boton.href;
          });
        });
      });
    }

    if (todas) {
      var textoTodas = todas.textContent;
      todas.addEventListener('click', function () {
        if (usarMenuCompartir) {
          compartir(todas, botones, textoTodas, function () {
            descargarUnaPorUna(botones);
          });
        } else {
          descargarUnaPorUna(botones);
        }
      });
    }
  });
})();
