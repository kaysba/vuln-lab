---
name: vuln-lab-ui
description: Design system and visual direction for the vuln-lab. Use for any interface creation or change (pages, components, CSS, animations, sounds).
---

# Visual direction: retro operator console

Single concept: an operations-room terminal, not a regular website.

Never mix it with another concept.

## Palette (mandatory)

- Background: #0b0f0c
- Text: #c8f7c5
- Accent: #39ff88
- Danger: #ff3b3b
- Warning: #ffb000

## Typography

- Main mono: IBM Plex Mono
- Headings: a distinctive display font, never Inter, Roboto or Arial

## Lab-specific elements

- Easy / medium / hard levels presented as threat levels
- Navigation styled as a shell prompt
- Very subtle scanlines or glitch, never on text meant to be read

## Game feel

The lab should feel like a hacking video game, not a static page. Every

interaction gets feedback. Keep it snappy: animations under 300ms except

the break-in sequence.

### Selection feedback

- Selected items (menu entries, challenge rows, tabs) blink briefly in the
  accent color: 2 to 3 blinks, about 100ms each, then settle on a solid
  highlight (accent background, dark text).
- Hovered items show a blinking block cursor before the label.
- Focus states must be as visible as hover states (keyboard users).

### Break-in sequence (when a vulnerability is exploited)

Triggered once, when the player successfully captures the flag or exploits

the vulnerability:

1. The screen flashes danger red (#ff3b3b) with a short, hard glitch.
2. Full-screen overlay with "SYSTEM HACKED" in the display font, with a
   typewriter or glitch reveal.
3. A few lines of fake log output underneath, e.g. "access granted",
   "root shell opened", "flag captured".
4. After about 2.5 seconds the overlay fades out and the flag or result
   panel appears in accent green.
- Flash safety: never flash faster than 3 times per second. Use a slow
  red pulse, not a strobe.
- Must be skippable (click or Escape).

### Sound effects

- Generate sounds with the Web Audio API (oscillators and noise bursts),
  no audio files, so the lab stays self-contained.
- Sounds needed: menu move (short tick), select (two-tone blip),
  wrong attempt (low buzz), success (rising arpeggio), break-in
  (alarm sweep plus noise burst), terminal typing (very quiet clicks).
- Audio can only start after a user gesture: create the AudioContext on
  the first click or keypress.
- Keep volume low by default.
- Always provide a visible mute toggle (a "[SND: ON]" style control in the
  top bar) and remember the choice in localStorage inside a try/catch.

### Other game touches

- Wrong attempt: brief red shake on the input plus a "ACCESS DENIED" line.
- Level completion: small "+XP" or score line in the accent color.
- Boot sequence on first load: a few lines of fake system startup text,
  skippable.

### Accessibility

- Respect `prefers-reduced-motion`: disable blinking, shake and glitch,
  keep only static color changes and the text of the break-in message.
- Never rely on color alone: levels and statuses also carry a text label
  (EASY, MEDIUM, HARD, LOCKED).

## Forbidden

- Rounded cards with soft shadows
- Purple gradients
- Emojis as icons
- Centered, symmetrical layouts
- Default Tailwind look

## Method

1. Propose 3 quick HTML mockups before building.
2. After each change, open the page with Playwright, take a screenshot,
   critique it, fix it.
3. Test the break-in sequence and the sounds in the browser before
   considering the work done.
