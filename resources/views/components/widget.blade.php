@if($enabled && $tokenUrl !== '' && $configUrl !== '')
<script>
(function () {
  'use strict';

  var state = {
    tokenUrl: @json($tokenUrl),
    configUrl: @json($configUrl),
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
        var resolvedConfigUrl = state.configUrl + '?token=' + encodeURIComponent(data.token);

        return loadSdk().then(function () {
          state.instance = new Landbot.Livechat({
            configUrl: resolvedConfigUrl
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
  window.addEventListener('click', bootLandbot, { once: true, passive: true });
  window.addEventListener('focus', bootLandbot, { once: true });

  window.setTimeout(bootLandbot, 1500);
}());
</script>
@endif
