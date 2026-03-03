export default {
  nav: {
    about: "Über mich",
    skills: "Fähigkeiten",
    projects: "Projekte",
    contact: "Kontakt",
  },
  hero: {
    subtitle: "SPEZIALISIERT AUF SAUBERE APIs, AUTH-FLOWS UND DATENBANKGESTÜTZTE BACKENDS.",
    ctaProjects: "Projekte ansehen",
    ctaContact: "Kontakt aufnehmen",
    scrollDown: "Nach unten scrollen",
  },
  about: {
    title: "Über mich",
    subtitle: "Lerne die Person hinter dem Code kennen",
    role: "Backend-Entwickler",
    location: "Leipzig, Deutschland",
    focus: "Fokus auf Backend-Rollen",
    whenNotCoding: "Wenn ich nicht code:",
    downloadResume: "Lebenslauf herunterladen",
    desc1:
      "Ich baue verlässliche Backend-Systeme mit PHP/Symfony und Drupal – saubere APIs, testbare Architektur und pragmatische Umsetzung.",
    desc2: "Außerdem experimentiere ich mit Python-Projekten (arabische Spracherkennung und Schlagwortextraktion).",
    stats: {
      yearsCoding: { n: "4+", t: "Jahre Programmierung" },
      projects: { n: "20+", t: "Projekte" },
    },
    hobbies: ["Mate", "Gaming", "Open Source"],
    cards: [
      { title: "Hintergrund", text: "Enterprise-Erfahrung in Deutschland (adesso)." },
      { title: "Stärken", text: "Backend-Architektur, APIs, Auth, Stabilität." },
      { title: "Extra", text: "Python-Experimente in arabischer ASR." },
      { title: "Sprachen", text: "Arabisch (Muttersprache), Deutsch (C1), Englisch (B2)." },
    ],
  },
  skills: {
    title: "Fähigkeiten & Technologien",
    subtitle: "Ein umfassender Überblick über meine technische Expertise",
    groups: [
      {
        title: "Frontend-Entwicklung",
        desc: "Moderne, responsive Benutzeroberflächen erstellen",
        items: [
          { name: "Vue.js", level: "Fortgeschritten" },
          { name: "JavaScript", level: "Fortgeschritten" },
          { name: "Twig", level: "Fortgeschritten" },
          { name: "Responsive UI", level: "Fortgeschritten" },
        ],
      },
      {
        title: "Backend-Entwicklung",
        desc: "Robuste Serveranwendungen und APIs entwickeln",
        items: [
          { name: "PHP", level: "Fortgeschritten" },
          { name: "Symfony", level: "Fortgeschritten" },
          { name: "Drupal", level: "Fortgeschritten" },
          { name: "SQL", level: "Fortgeschritten" },
          { name: "REST-APIs", level: "Fortgeschritten" },
        ],
      },
      {
        title: "Daten / KI (Nebenprojekte)",
        desc: "Pragmatisches ML-Engineering und Daten-Workflows",
        items: [
          { name: "Python", level: "Fortgeschritten" },
          { name: "ASR", level: "Fortgeschritten" },
          { name: "faster-whisper", level: "Fortgeschritten" },
          { name: "Schlagwortextraktion", level: "Mittel" },
        ],
      },
      {
        title: "DevOps & Qualität",
        desc: "Entwicklungs- und Deployment-Workflows optimieren",
        items: [
          { name: "Git", level: "Fortgeschritten" },
          { name: "Docker", level: "Mittel" },
          { name: "Testing", level: "Fortgeschritten" },
          { name: "Linux", level: "Fortgeschritten" },
        ],
      },
    ],
    strip: [
      { n: "30+", t: "Technologien" },
      { n: "4+", t: "Jahre Erfahrung" },
      { n: "10+", t: "Abgeschlossene Projekte" },
      { n: "7", t: "Zufriedene Kunden" },
    ],
  },
  projects: {
    title: "Projekte",
    subtitle: "Ausgewählte Arbeiten",
    filters: ["Alle", "Backend", "KI", "Enterprise", "IoT"],
    viewDetails: "Details ansehen",
    categories: {
      Backend: "Backend",
      AI: "KI",
      Enterprise: "Enterprise",
      IoT: "IoT",
    },
  },
  project: {
    back: "Zurück",
    overview: "Überblick",
    keyHighlights: "Wichtigste Highlights",
    stack: "Technologien",
    addHeader: "Auf dieser Seite ergänzen",
    addList: [
      "1–2 Screenshots (oder ein kurzes GIF), die UI/Output zeigen",
      "Ein kurzes Architekturdiagramm (optional)",
      "Ein klarer Hinweis, was öffentlich vs. privat ist (Kundenarbeit)",
      "Zahlen falls vorhanden (Latenz, Kosten, Volumen, Stabilität)",
    ],
    items: {
      genehub: {
        title: "GeneHub",
        tagline: "Arabische ASR + Schlagwortextraktion (syrischer Dialekt)",
        summary:
          "Eine produktionsorientierte Speech-to-Text-Komponente, die gesprochene Anfragen in strukturierte Schlagwörter für eine Servicesuche umwandelt.",
        highlights: [
          "Schnelle Inferenz-Pipeline (GPU-fähig), getuntes VAD & Post-Processing",
          "Dataset-Tools: Segmentierung + CSV-Mapping fürs Fine-Tuning",
          "Schlagwortextraktion für lokale Gebiete & Dienste",
        ],
        category: "KI",
      },
      jitak: {
        title: "Jitak",
        tagline: "Lieferplattform-Backend mit mehreren Rollen (Symfony)",
        summary:
          "Ein Backend-First-System mit modularen Domänen, rollenbewusster Auth, Testbarkeit und klaren API-Grenzen.",
        highlights: [
          "JWT + Refresh-Token-Flow; rollenbewusste APIs",
          "CQRS-ähnliche Handler; starker Test-Loop über In-Memory Stores",
          "Order-Lebenszyklus & Zustandsübergänge mit klaren Domänenregeln",
        ],
        category: "Backend",
      },
      enterprise: {
        title: "Enterprise-Systeme",
        tagline: "adesso — Symfony/Drupal Delivery & Refactoring",
        summary:
          "Professionelle Arbeit mit Fokus auf Feature-Delivery, Stabilität und Refactoring in komplexen Produktivcodebasen.",
        highlights: [
          "Legacy-Codepfade refaktoriert und Stabilität verbessert",
          "Features mit Stakeholder-Abstimmung geliefert",
          "Pragmatische Bugfixes und Wartbarkeitsverbesserungen",
        ],
        category: "Enterprise",
      },
      "smart-room": {
        title: "Smart Room Focus Engine",
        tagline: "Arduino-Sensoren + Feedback-Loop",
        summary:
          "Ein kleines IoT-Prototyping, das Sensoren und UI-Feedback kombiniert, um Fokusgewohnheiten und Raumwahrnehmung zu unterstützen.",
        highlights: [
          "Sensor-Integration + einfache Regel-Engine",
          "LCD-UI-Feedback und reproduzierbares Wiring-Setup",
        ],
        category: "IoT",
      },
    },
  },
  contact: {
    title: "Kontakt aufnehmen",
    subtitle: "Kontakt",
    haveProject: "Haben Sie ein Projekt im Sinn?",
    lead: "Ich bin offen für Backend-Möglichkeiten (PHP/Symfony/Drupal) und gemeinsame Projekte.",
    emailCopied: { copied: "Kopiert", copy: "Meine E-Mail kopieren" },
    sendMessage: "Nachricht senden",
    placeholders: {
      name: "Name *",
      email: "E-Mail *",
      subject: "Betreff *",
      message: "Nachricht *",
    },
    send: "Nachricht senden",
    address: "Leipzig, Deutschland",
    phone: "+49 ...",
    footer: "Alle Rechte vorbehalten.",
  },
} as const;
