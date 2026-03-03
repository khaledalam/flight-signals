# Demo Media

This directory holds demo recordings for the README.

## How to record

Using [VHS](https://github.com/charmbracelet/vhs) (recommended):

```bash
# Install VHS
brew install charmbracelet/tap/vhs

# Record the demo tape
vhs docs/media/demo.tape
```

Using manual screen recording:

1. Start the app: `./vendor/bin/sail up -d && ./vendor/bin/sail artisan migrate`
2. Open a terminal and run the curl commands from the README
3. Record using any screen capture tool (e.g., Kap, OBS, or `asciinema`)
4. Convert to GIF: `ffmpeg -i demo.mp4 -vf "fps=15,scale=800:-1" demo.gif`
5. Place `demo.gif` in this directory

## Expected files

- `demo.gif` — animated terminal demo for the README header
- `demo.tape` — VHS tape file (optional, for reproducible recordings)
