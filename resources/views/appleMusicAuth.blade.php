<head>
    <title>TuneSwap API Apple Music Authentication</title>
    <script src="https://js-cdn.music.apple.com/musickit/v1/musickit.js"></script>

    <meta name="apple-music-developer-token" content="{{ env("APPLE_MUSIC_TOKEN") }}">
    <meta name="apple-music-app-name" content="TuneSwap">
    <meta name="apple-music-app-build" content="">
    <meta name="apple-music-app-icon" content="https://tuneswap.app/logo192.png">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        window.addEventListener("musickitloaded", () => {
            configure();

            console.log("MusicKit Loaded");
        });

        const configure = () => {
            MusicKit.configure({
                developerToken: "{{ env("APPLE_MUSIC_TOKEN") }}",
                app: {
                    name: "TuneSwap",
                    build: ""
                }
            });
        }

        const onButtonClick = () => {
            let music = MusicKit.getInstance();

            music.authorize().then((token) => {
                console.log("authorized");
                console.log("token is: " + token);

                window.location.href = "{{URL::to("/api/applemusic/auth")}}" + "?apiToken=" + encodeURIComponent("{{ $apiToken }}") + "&token=" + encodeURIComponent(token);
            });
        }
    </script>
    <link rel="stylesheet" href="/normalize.css"/>
    <link rel="stylesheet" href="/skeleton.css"/>
</head>

<body>
<div style="text-align: center;">
    <br/>
    <br/>
    <img src="/AppleMusicLogo.png" style="height: 5em;"/>
    <br/>
    <br/>
    <h5>Click below to be redirected to Apple for authentication with Apple Music. You are agreeing to allow TuneSwap to
        access information in Apple Music account.</h5>

    <a href="#" id="apple-music-authorize" onclick="onButtonClick()"><img src="/applesignin.png"
                                                                          style="height: 3em;"/></a>
    {{--    <button id="apple-music-authorize" onclick="onButtonClick()" class="button button-primary">Sign In</button>--}}
</div>
</body>

<style>
    body {
        background: rgb(208, 208, 224);
        background: radial-gradient(circle, rgba(208, 208, 224, 1) 29%, rgba(181, 162, 242, 1) 100%);
    }

    .loginButton {
        background: #5E5DF0;
        border-radius: 999px;
        box-shadow: #5E5DF0 0 10px 20px -10px;
        box-sizing: border-box;
        color: #FFFFFF;
        cursor: pointer;
        font-family: Inter, Helvetica, "Apple Color Emoji", "Segoe UI Emoji", NotoColorEmoji, "Noto Color Emoji", "Segoe UI Symbol", "Android Emoji", EmojiSymbols, -apple-system, system-ui, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", sans-serif;
        font-size: 16px;
        font-weight: 700;
        line-height: 24px;
        opacity: 1;
        outline: 0 solid transparent;
        padding: 8px 18px;
        user-select: none;
        -webkit-user-select: none;
        touch-action: manipulation;
        width: fit-content;
        word-break: break-word;
        border: 0;
    }
</style>
