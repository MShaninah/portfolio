export default {
  nav: {
    about: "About",
    skills: "Skills",
    projects: "Projects",
    contact: "Contact",
    home: "Home",
  },
  hero: {
    subtitle: "SPECIALIZING IN CLEAN APIs, AUTH FLOWS, AND DATABASE-DRIVEN BACKEND SYSTEMS.",
    ctaProjects: "View Projects",
    ctaContact: "Contact Me",
    scrollDown: "Scroll down",
  },
  about: {
    title: "About Me",
    subtitle: "Get to know the person behind the code",
    role: "Backend Developer",
    location: "Leipzig, Germany",
    focus: "Focused on backend roles",
    whenNotCoding: "When I'm not coding:",
    downloadResume: "Download Resume",
    desc1:
      "I build reliable backend systems with PHP/Symfony and Drupal—clean APIs, testable architecture, and pragmatic delivery.",
    desc2: "I also experiment with Python projects (Arabic ASR and keyword extraction).",
    stats: {
      yearsCoding: { n: "4+", t: "Years Coding" },
      projects: { n: "20+", t: "Projects" },
    },
    hobbies: ["Mate", "Gaming", "Open source"],
    cards: [
      { title: "Background", text: "Enterprise experience in Germany (adesso)." },
      { title: "Strengths", text: "Backend architecture, APIs, auth, stability." },
      { title: "Extra", text: "Python experiments in Arabic ASR." },
      { title: "Languages", text: "Arabic (native), German (C1), English (B2)." },
    ],
  },
  skills: {
    title: "Skills & Technologies",
    subtitle: "A comprehensive overview of my technical expertise",
    groups: [
      {
        title: "Frontend Development",
        desc: "Creating modern, responsive user interfaces",
        items: [
          { name: "Vue.js", level: "Advanced" },
          { name: "JavaScript", level: "Advanced" },
          { name: "Twig", level: "Advanced" },
          { name: "Responsive UI", level: "Advanced" },
        ],
      },
      {
        title: "Backend Development",
        desc: "Building robust server-side applications and APIs",
        items: [
          { name: "PHP", level: "Advanced" },
          { name: "Symfony", level: "Advanced" },
          { name: "Drupal", level: "Advanced" },
          { name: "SQL", level: "Advanced" },
          { name: "REST APIs", level: "Advanced" },
        ],
      },
      {
        title: "Data / AI (side projects)",
        desc: "Pragmatic ML engineering and data workflows",
        items: [
          { name: "Python", level: "Advanced" },
          { name: "ASR", level: "Advanced" },
          { name: "faster-whisper", level: "Advanced" },
          { name: "Keyword Extraction", level: "Intermediate" },
        ],
      },
      {
        title: "DevOps & Quality",
        desc: "Streamlining development and deployment workflows",
        items: [
          { name: "Git", level: "Advanced" },
          { name: "Docker", level: "Intermediate" },
          { name: "Testing", level: "Advanced" },
          { name: "Linux", level: "Advanced" },
        ],
      },
    ],
    strip: [
      { n: "30+", t: "Technologies" },
      { n: "4+", t: "Years Experience" },
      { n: "10+", t: "Projects Completed" },
      { n: "7", t: "Happy Clients" },
    ],
  },
  projects: {
    title: "Projects",
    subtitle: "Selected work",
    filters: ["All", "Backend", "AI", "Enterprise", "IoT"],
    viewDetails: "View details",
    categories: {
      Backend: "Backend",
      AI: "AI",
      Enterprise: "Enterprise",
      IoT: "IoT",
    },
  },
  project: {
    back: "Back",
    overview: "Overview",
    keyHighlights: "Key highlights",
    stack: "Stack",
    addHeader: "Add to this page",
    addList: [
      "1–2 screenshots (or a short GIF) showing the UI/output",
      "A short architecture diagram (optional)",
      "A clear note on what’s public vs private (client work)",
      "Numbers if available (latency, cost, volume, stability)",
    ],
    // Per-project overrides by slug (optional)
    items: {
      genehub: {
        title: "GeneHub",
        tagline: "Arabic ASR + keyword extraction (Syrian dialect friendly)",
        summary:
          "A production-oriented speech-to-text component that converts spoken queries into structured keywords for a services search app.",
        highlights: [
          "Fast inference pipeline (GPU-ready), tuned VAD and post-processing",
          "Dataset tooling: segmentation + CSV mapping for fine-tuning",
          "Keyword extraction tailored to local areas & services",
        ],
        category: "AI",
      },
      jitak: {
        title: "Jitak",
        tagline: "Multi-role delivery platform backend (Symfony)",
        summary:
          "A backend-first platform emphasizing modular domains, role-aware auth, testability, and clean API boundaries.",
        highlights: [
          "JWT + refresh token flow; role-aware APIs",
          "CQRS-style handlers; strong test loop via in-memory stores",
          "Order lifecycle & state transitions with clear domain rules",
        ],
        category: "Backend",
      },
      enterprise: {
        title: "Enterprise Systems",
        tagline: "adesso — Symfony/Drupal delivery & refactoring",
        summary:
          "Professional work focused on feature delivery, stability, and refactoring in complex production codebases.",
        highlights: [
          "Refactored legacy code paths and improved stability",
          "Delivered features with stakeholder alignment",
          "Pragmatic bug fixing and maintainability improvements",
        ],
        category: "Enterprise",
      },
      "smart-room": {
        title: "Smart Room Focus Engine",
        tagline: "Arduino sensors + feedback loop",
        summary:
          "A small IoT prototype combining sensors and UI feedback to support focus habits and room awareness.",
        highlights: [
          "Sensor integration + basic rule engine",
          "LCD UI feedback and reproducible wiring setup",
        ],
        category: "IoT",
      },
    },
  },
  contact: {
    title: "Get In Touch",
    subtitle: "Contact",
    haveProject: "Have a project in mind?",
    lead: "I’m open to backend opportunities (PHP/Symfony/Drupal) and collaborative projects.",
    emailCopied: { copied: "Copied", copy: "Copy my email" },
    sendMessage: "Send a Message",
    placeholders: {
      name: "Name *",
      email: "Email *",
      subject: "Subject *",
      message: "Message *",
    },
    send: "Send Message",
    address: "Leipzig, Germany",
    phone: "+49 ...",
    footer: "All rights reserved.",
  },
} as const;
