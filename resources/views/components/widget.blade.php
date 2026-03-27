@if($enabled && $tokenUrl !== '' && $configUrl !== '')
<script>
(function () {
  'use strict';

  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var state = {
    tokenUrl: @json($tokenUrl),
    configUrl: @json($configUrl),
    csrf: csrfMeta ? csrfMeta.content : '',
    instance: null,
    booting: false,
    sdkPromise: null
  };

  function warn(message, error) {
    if (!window.console || !window.console.warn) {
      return;
    }

    if (typeof error === 'undefined') {
      window.console.warn(message);
      return;
    }

    window.console.warn(message, error);
  }

  function loadSdk() {
    if (state.sdkPromise) {
      return state.sdkPromise;
    }

    state.sdkPromise = new Promise(function (resolve, reject) {
      if (window.Landbot && window.Landbot.Livechat) {
        resolve();
        return;
      }

      var script = document.createElement('script');
      script.type = 'module';
      script.async = true;
      script.src = 'https://cdn.landbot.io/landbot-3/landbot-3.0.0.mjs';
      script.onload = function () {
        resolve();
      };
      script.onerror = function () {
        state.sdkPromise = null;
        reject(new Error('Failed to load Landbot SDK.'));
      };

      document.head.appendChild(script);
    });

    return state.sdkPromise;
  }

  function bootLandbot() {
    if (state.booting || state.instance) {
      return;
    }

    if (!state.csrf) {
      warn('[landbot-secure] Missing CSRF meta tag.');
      return;
    }

    state.booting = true;

    fetch(state.tokenUrl, {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('token-request-failed:' + response.status);
        }

        return response.json();
      })
      .then(function (data) {
        return fetch(state.configUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': state.csrf
          },
          body: JSON.stringify({ token: data.token })
        });
      })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('config-request-failed:' + response.status);
        }

        return response.json();
      })
      .then(function (botConfig) {
        return loadSdk().then(function () {
          var blob = new Blob([JSON.stringify(botConfig)], {
            type: 'application/json'
          });
          var blobUrl = URL.createObjectURL(blob);

          state.instance = new Landbot.Livechat({
            configUrl: blobUrl
          });
        });
      })
      .catch(function (error) {
        state.booting = false;
        warn('[landbot-secure] Init failed.', error);
      });
  }

  window.addEventListener('mouseover', bootLandbot, { once: true });
  window.addEventListener('touchstart', bootLandbot, { once: true, passive: true });
  window.addEventListener('scroll', bootLandbot, { once: true, passive: true });
}());
</script>
@endif
