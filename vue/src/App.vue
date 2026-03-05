<template>
  <div :class="['min-h-screen bg-black text-white', isLight ? 'theme-invert' : '']">
    <TopNav @nav="scrollTo" :is-light="isLight" @toggle-theme="toggleTheme" />
    <NeonBG />
    <RatingPill />

    <div v-if="isAdmin || isLoginRoute">
      <AdminDashboard :is-light="isLight" @toggle-theme="toggleTheme" />
    </div>

    <Transition v-else name="fade" mode="out-in">
      <div v-if="selectedProject" key="detail">
        <ProjectDetail :project="selectedProject" @back="selected = null" />
      </div>

      <main v-else key="home">
        <section id="home" v-reveal class="mx-auto max-w-6xl px-4 pb-10 pt-12 sm:pt-16">
          <div>
            <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">
              MOHAMAD SHANINAH,
              <span class="block bg-gradient-to-r from-indigo-300 via-white to-emerald-200 bg-clip-text text-transparent">
                {{ currentText }}<span class="cursor">|</span>
              </span>
            </h1>

            <p class="mt-3 max-w-3xl text-sm sm:text-base leading-relaxed text-white/70">
              SPECIALIZING IN CLEAN APIs, CLEAN AUTH FLOWS, AND SCALABLE BACKEND ARCHITECTURE.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button
                  class="btn-primary"
                  @click="scrollTo('projects')"
              >
                View Projects
                <ArrowUpRight class="ml-2 h-4 w-4" />
              </button>

              <button
                  class="btn-outline"
                  @click="scrollTo('contact')"
              >
                Contact Me
                <Mail class="ml-2 h-4 w-4" />
              </button>
            </div>

            <div class="mt-6 flex items-center gap-2">
              <button
                  v-for="s in socials"
                  :key="s.label"
                  class="btn-icon"
                  :aria-label="s.label"
                  @click="open(s.href)"
              >
                <component :is="s.icon" class="h-4 w-4 text-white/70" />
              </button>
            </div>

            <div
              class="mt-10 inline-flex items-center gap-3 text-xs text-white/50 cursor-pointer hover:text-white focus:text-white outline-none"
              @click="scrollTo('about')"
              role="button"
              tabindex="0"
              aria-label="Scroll to About Me section"
              @keydown.enter.prevent="scrollTo('about')"
              @keydown.space.prevent="scrollTo('about')"
            >
              <span class="h-px w-10 bg-white/15" />
              <span>Scroll down</span>
            </div>
          </div>
        </section>

        <section id="about" v-reveal class="mx-auto max-w-6xl px-4 py-12">
          <SectionKicker title="About Me" subtitle="Get to know the person behind the code" />

          <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <GlassCard class="overflow-hidden">
              <div class="relative p-6">
                <div class="absolute inset-0 bg-[radial-gradient(500px_circle_at_30%_0%,rgba(99,102,241,0.25),transparent_60%)]" />
                <div class="relative space-y-4">
                  <div class="flex items-center gap-3">
                    <div class="h-14 w-14 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                      <div class="grid h-full w-full place-items-center text-xs text-white/55"><img
                          src="./assets/profile-pic.jpg" height="1024" width="1024"/></div>
                    </div>
                    <div>
                      <div class="text-sm font-semibold text-white">Mohamad Shaninah</div>
                      <div class="text-xs text-white/55">Backend Developer</div>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-3">
                    <div v-for="s in aboutStats" :key="s.t" class="rounded-2xl border border-white/10 bg-white/5 p-3">
                      <div class="text-lg font-semibold text-white">{{ s.n }}</div>
                      <div class="text-xs text-white/55">{{ s.t }}</div>
                    </div>
                  </div>

                  <div class="space-y-2 text-sm text-white/70">
                    <div class="flex items-center gap-2">
                      <MapPin class="h-4 w-4" /> Leipzig, Germany
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="h-2 w-2 rounded-full bg-white/40" /> Focused on backend roles
                    </div>
                  </div>

                  <div>
                    <div class="text-sm font-semibold text-white/90">When I'm not coding:</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <span v-for="x in hobbies" :key="x" class="badge-outline">{{ x }}</span>
                    </div>
                  </div>

                  <button class="btn-outline w-full" @click="">
                    <Download class="mr-2 h-4 w-4" /> Download Resume
                  </button>
                </div>
              </div>
            </GlassCard>

            <GlassCard class="lg:col-span-2">
              <div class="p-6">
                <p class="text-sm leading-relaxed text-white/70">
                  I build reliable backend systems with PHP/Symfony and Drupal—clean APIs, testable architecture, and pragmatic delivery.
                  I also experiment with Python projects (Arabic ASR and keyword extraction).
                </p>

                <div class="my-5 h-px bg-white/10" />

                <div class="grid gap-4 sm:grid-cols-2">
                  <div
                      v-for="c in aboutCards"
                      :key="c.title"
                      class="rounded-2xl border border-white/10 bg-white/5 p-4"
                  >
                    <div class="text-sm font-semibold text-white">{{ c.title }}</div>
                    <div class="mt-1 text-sm text-white/65">{{ c.text }}</div>
                  </div>
                </div>
              </div>
            </GlassCard>
          </div>
        </section>

        <section id="skills" v-reveal class="mx-auto max-w-6xl px-4 py-12">
          <SectionKicker
              title="Skills & Technologies"
              subtitle="A comprehensive overview of my technical expertise"
          />

          <div class="mt-8 grid gap-4 md:grid-cols-2">
            <GlassCard v-for="g in skillGroups" :key="g.title">
              <div class="p-6">
                <div class="text-base font-semibold text-white">{{ g.title }}</div>
                <div class="mt-1 text-sm text-white/60">{{ g.desc }}</div>

                <div class="mt-4 space-y-3">
                  <div
                      v-for="it in g.items"
                      :key="it.name"
                      class="skill-item group flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2 relative overflow-hidden"
                      :style="{ '--brand': brandColor(it.name) }"
                  >
                    <!-- Fading logo from the left -->
                    <div class="skill-logo absolute left-3 flex h-6 w-6 items-center justify-center rounded-md bg-white/10 text-[10px] text-white/70 opacity-0 -translate-x-2 transition-all duration-300 ease-out group-hover:opacity-100 group-hover:translate-x-0 select-none overflow-hidden">
                      <template v-if="skillLogo(it.name)">
                        <img :src="skillLogo(it.name)!" :alt="it.name + ' logo'" class="h-6 w-6 object-contain" loading="lazy" referrerpolicy="no-referrer" @error="onIconError" />
                        <span class="fallback-text hidden">{{ logoText(it.name) }}</span>
                      </template>
                      <template v-else>
                        <span>{{ logoText(it.name) }}</span>
                      </template>
                    </div>

                    <!-- Reserve space so text doesn't jump when logo appears -->
                    <div class="flex items-center pl-8">
                      <span class="skill-title text-sm text-white/80 transition-transform duration-300 ease-out group-hover:translate-x-1">{{ it.name }}</span>
                    </div>

                    <span class="badge-secondary z-10">{{ it.level }}</span>
                  </div>
                </div>
              </div>
            </GlassCard>
          </div>

          <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="s in stripStats" :key="s.t" class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <div class="text-2xl font-semibold text-white">{{ s.n }}</div>
              <div class="mt-1 text-xs text-white/55">{{ s.t }}</div>
            </div>
          </div>
        </section>
        <section id="projects" v-reveal class="mx-auto max-w-6xl px-4 py-12">
          <SectionKicker title="Projects" subtitle="Selected work" />

          <div class="mt-6 flex flex-wrap gap-2">
            <button
                v-for="x in filters"
                :key="x"
                :class="filter === x ? 'btn-pill-active' : 'btn-pill'"
                @click="filter = x"
            >
              {{ x }}
            </button>
          </div>

          <div class="mt-8 grid gap-4 md:grid-cols-2">
            <ProjectCard
                v-for="p in filteredProjects"
                :key="p.id"
                :p="p"
                @open="selected = $event"
            />
          </div>
        </section>

        <section id="contact" v-reveal class="mx-auto max-w-6xl px-4 py-14">
          <SectionKicker title="Get In Touch" subtitle="Contact" />

          <GlassCard class="mt-8">
            <div class="p-6 sm:p-8">
              <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-4">
                  <div>
                    <div class="text-sm font-semibold text-white">Have a project in mind?</div>
                    <p class="mt-2 text-sm text-white/65 leading-relaxed">
                      I’m open to backend opportunities (PHP/Symfony/Drupal) and collaborative projects.
                    </p>
                  </div>

                  <div class="h-px bg-white/10" />

                  <div class="space-y-3 text-sm text-white/70">
                    <div class="flex items-center gap-2"><Mail class="h-4 w-4" /> {{ email }}</div>
                    <div class="flex items-center gap-2"><Phone class="h-4 w-4" />
                      {{ phone }}</div>
                    <div class="flex items-center gap-2"><MapPin class="h-4 w-4" /> Leipzig, Germany</div>
                  </div>

                  <div class="flex flex-wrap items-center gap-2">
                    <button class="btn-outline" @click="copyEmail">
                      <Copy class="mr-2 h-4 w-4" />
                      {{ copied ? 'Copied' : 'Copy my email' }}
                    </button>

                    <div class="flex items-center gap-2">
                      <button class="btn-icon" @click="open('https://github.com/MShaninah')"><Github class="h-4 w-4" /></button>
                      <button class="btn-icon" @click="open('https://www.linkedin.com/in/mohamad-shaninah-5231761ab')"><Linkedin class="h-4 w-4" /></button>
                    </div>
                  </div>
                </div>

                <div class="lg:col-span-2">
                  <div class="text-sm font-semibold text-white">Send a Message</div>
                  <div class="mt-4 space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                      <input class="input" placeholder="Name *" />
                      <input class="input" placeholder="Email *" />
                    </div>
                    <input class="input" placeholder="Subject *" />
                    <textarea class="textarea" placeholder="Message *"></textarea>

                    <div class="flex justify-end">
                      <button class="btn-primary">Send Message</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </GlassCard>

          <div class="mt-10 space-y-2 text-center text-xs text-white/40">
            <div>© {{ new Date().getFullYear() }} Mohamad Shaninah. All rights reserved.</div>
          </div>
        </section>
      </main>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import {computed, markRaw, onMounted, ref, watch} from "vue";
import {i18n} from "./i18n";
import {getPortfolio} from "./api/portfolio"
import { getToken } from "./api/admin"

import {ArrowUpRight, Copy, Download, Github, Linkedin, Mail, MapPin, Phone,} from "lucide-vue-next";
import NeonBG from './components/NeonBG.vue'
import GlassCard from './components/GlassCard.vue'
import SectionKicker from './components/SectionKicker.vue'
import TopNav from './components/TopNav.vue'
import RatingPill from './components/RatingPill.vue'
import ProjectCard from './components/ProjectCard.vue'
import ProjectDetail from './components/ProjectDetail.vue'
import AdminDashboard from './components/AdminDashboard.vue'

const THEME_KEY = 'theme'
const isLight = ref(false)

const isAdmin = ref(false)

const isLoginRoute = ref(false)

onMounted(() => {
  const saved = typeof localStorage !== 'undefined' ? localStorage.getItem(THEME_KEY) : null
  if (saved === 'light') {
    isLight.value = true
  } else if (saved === 'dark') {
    isLight.value = false
  } else {
    try {
      isLight.value = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches
    } catch {
      isLight.value = false
    }
  }

  try { isAdmin.value = !!getToken() } catch { isAdmin.value = false }

  const onToken = (e: any) => { isAdmin.value = !!e?.detail }
  window.addEventListener('admin-token', onToken as EventListener)
  ;(onToken as any)._off = () => window.removeEventListener('admin-token', onToken as EventListener)

  try {
    isLoginRoute.value = window.location && window.location.pathname === '/login'
    window.addEventListener('popstate', () => {
      isLoginRoute.value = window.location.pathname === '/login'
    })
  } catch {}
})

function toggleTheme() {
  isLight.value = !isLight.value
  try {
    localStorage.setItem(THEME_KEY, isLight.value ? 'light' : 'dark')
  } catch {}
}

const socials = [
  { label: "GitHub", href: "https://github.com/MShaninah", icon: markRaw(Github) },
  { label: "LinkedIn", href: "https://www.linkedin.com/in/mohamad-shaninah-5231761ab", icon: markRaw(Linkedin) },
  { label: "Email", href: "mailto:shaninah.mohammad@gmail.com", icon: markRaw(Mail) },
] as const

type Project = {
  id: string;
  title: string;
  tagline: string;
  summary: string;
  highlights: string[];
  stack: string[];
  links: { label: string; href: string; icon?: any }[];
  year: string;
  category: "Backend" | "AI" | "Enterprise" | "IoT";
};

const titles = ["FULLSTACK DEVELOPER", "ML ENGINEER"];
const currentText = ref("");
let titleIndex = 0;
let charIndex = 0;
let isDeleting = false;
const typeSpeed = 150;

const type = () => {
  const currentFullText: string = titles[titleIndex] ?? "";

  if (isDeleting) {
    currentText.value = currentFullText.substring(0, charIndex - 1);
    charIndex--;
  } else {
    currentText.value = currentFullText.substring(0, charIndex + 1);
    charIndex++;
  }

  let delta = isDeleting ? typeSpeed / 2 : typeSpeed;

  if (!isDeleting && currentText.value === currentFullText) {
    delta = 2000; // Pause at the end
    isDeleting = true;
  } else if (isDeleting && currentText.value === "") {
    isDeleting = false;
    titleIndex = (titleIndex + 1) % titles.length;
    delta = 500;
  }

  setTimeout(type, delta);
};

onMounted(() => {
  type();
});

const email = ref("")
const phone = ref("")

const projects = ref<Project[]>([]);

const filters = ["All", "Backend", "AI", "Enterprise", "IoT"] as const;
const filter = ref<(typeof filters)[number]>("All");

const selected = ref<string | null>(null);
const selectedProject = computed(() =>
    selected.value ? projects.value.find((p) => p.id === selected.value) ?? null : null
);

const filteredProjects = computed(() => {
  if (filter.value === "All") return projects.value;
  return projects.value.filter((p) => p.category === filter.value);
});

const copied = ref(false);

const aboutStats = [
  { n: "4+", t: "Years Coding" },
  { n: "20+", t: "Projects" },
];

const hobbies = ["Mate", "Gaming", "Open source"];

const aboutCards = [
  { title: "Background", text: "Enterprise experience in Germany (adesso)." },
  { title: "Strengths", text: "Backend architecture, APIs, auth, stability." },
  { title: "Extra", text: "Python experiments in Arabic ASR." },
  { title: "Languages", text: "Arabic (native), German (C1), English (B2)." },
];

const stripStats = [
  { n: "30+", t: "Technologies" },
  { n: "4+", t: "Years Experience" },
  { n: "10+", t: "Projects Completed" },
  { n: "7", t: "Happy Clients" },
];

const skillGroups = [
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
];


async function loadPortfolio() {
  const lang = (i18n.global.locale.value as "en" | "de") ?? "en"
  const data = await getPortfolio(lang)
  projects.value = data.projects
  email.value = data.profile?.email ?? ""
  phone.value = data.profile?.phone ?? ""
}

onMounted(loadPortfolio)

// reload when language changes
watch(i18n.global.locale, loadPortfolio)

function scrollTo(id: string) {
  selected.value = null;
  const el = document.getElementById(id);
  if (!el) return;
  el.scrollIntoView({ behavior: "smooth", block: "start" });
}

function open(url: string) {
  window.open(url, "_blank");
}

async function copyEmail() {
  try {
    await navigator.clipboard.writeText(email.value);
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 1400);
  } catch {
    alert("Clipboard not available. Copy manually: " + email.value);
  }
}

function brandColor(name: string): string {
  const n = name.toLowerCase()
  if (n.includes('php')) return 'rgba(79, 93, 149, 0.45)'
  if (n.includes('symfony')) return 'rgba(66, 66, 66, 0.45)'
  if (n.includes('drupal')) return 'rgba(0, 123, 191, 0.45)'
  if (n.includes('sql')) return 'rgba(220, 38, 38, 0.45)'
  if (n.includes('python')) return 'rgba(53, 114, 165, 0.45)'
  if (n.includes('docker')) return 'rgba(0, 123, 191, 0.45)'
  if (n.includes('linux')) return 'rgba(50, 205, 50, 0.40)'
  if (n.includes('testing') || n.includes('test')) return 'rgba(234, 179, 8, 0.45)'
  if (n.includes('git')) return 'rgba(240, 80, 50, 0.45)'
  if (n.includes('asr') || n.includes('faster-whisper')) return 'rgba(16, 185, 129, 0.45)'
  return 'rgba(99, 102, 241, 0.30)'
}

function logoText(name: string): string {
  const parts = name.split(/[^A-Za-z0-9]+/).filter(Boolean)
  const letters = (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')
  return letters.toUpperCase() || name.slice(0, 1).toUpperCase()
}

type IconSource = 'dev' | 'si';

interface IconConfig {
  slug: string;
  source: IconSource;
  variant?: string;
}

const ICON_MAP: Record<string, IconConfig> = {
  'php': { slug: 'php', source: 'dev' },
  'symfony': { slug: 'symfony', source: 'dev' },
  'drupal': { slug: 'drupal', source: 'dev' },
  'sql': { slug: 'mysql', source: 'dev' },
  'postgres': { slug: 'postgresql', source: 'dev' },
  'mysql': { slug: 'mysql', source: 'dev' },
  'python': { slug: 'python', source: 'dev' },
  'docker': { slug: 'docker', source: 'dev' },
  'linux': { slug: 'linux', source: 'dev' },
  'git': { slug: 'git', source: 'dev' },
  'vue': { slug: 'vuejs', source: 'dev' },
  'javascript': { slug: 'javascript', source: 'dev' },
  'js': { slug: 'javascript', source: 'dev' },
  'twig': { slug: 'twig', source: 'si' },
  'rest': { slug: 'postman', source: 'dev', variant: 'plain' },
  'testing': { slug: 'jest', source: 'dev', variant: 'plain' },
  'test': { slug: 'jest', source: 'dev', variant: 'plain' },
  'whisper': { slug: 'openai', source: 'si' },
  'asr': { slug: 'openai', source: 'si' },
};

function skillLogo(name: string): string | null {
  const n = name.toLowerCase().trim();

  let config = ICON_MAP[n];

  if (!config) {
    const key = Object.keys(ICON_MAP).find(k => n.includes(k));
    if (key) config = ICON_MAP[key];
  }

  if (!config) return null;

  return config.source === 'dev'
      ? `https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/${config.slug}/${config.slug}-${config.variant || 'original'}.svg`
      : `https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/${config.slug}.svg`;
}
function onIconError(e: Event) {
  const img = e.target as HTMLImageElement
  if (!img) return
  img.classList.add('hidden')
  const sib = img.nextElementSibling as HTMLElement | null
  if (sib) sib.classList.remove('hidden')
}
</script>

<style scoped>
@reference "tailwindcss";
.btn-primary {
  @apply inline-flex items-center rounded-xl bg-white px-4 py-2 text-sm font-medium text-black hover:bg-white/90;
}
.btn-outline {
  @apply inline-flex items-center rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-white/90 hover:bg-white/10;
}
.btn-ghost {
  @apply inline-flex items-center rounded-xl px-3 py-2 text-sm font-medium text-white/80 hover:bg-white/5 hover:text-white;
}
.btn-icon {
  @apply inline-flex h-10 w-10 items-center justify-center rounded-xl text-white/70 hover:bg-white/5 hover:text-white;
}
.btn-pill {
  @apply rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm text-white/80 hover:bg-white/10;
}
.btn-pill-active {
  @apply rounded-full bg-white px-4 py-2 text-sm text-black hover:bg-white/90;
}
.badge-outline {
  @apply inline-flex items-center rounded-full border border-white/15 bg-transparent px-3 py-1 text-xs text-white/70;
}
.badge-secondary {
  @apply inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/70;
}
.input {
  @apply w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/35 outline-none focus:border-white/25 focus:ring-2 focus:ring-white/20;
}
.textarea {
  @apply min-h-[140px] w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/35 outline-none focus:border-white/25 focus:ring-2 focus:ring-white/20;
}
.link {
  @apply h-auto p-0 text-xs text-white/55 hover:text-white underline underline-offset-4;
}
.cursor {
  animation: blink 0.7s infinite;
}
@keyframes blink {
  from, to { opacity: 1; }
  50% { opacity: 0; }
}
.btn-icon{
  width: auto;
  height: auto;
}

.skill-item {
  --brand: rgba(99, 102, 241, 0.30);
  transition: box-shadow 300ms ease, transform 300ms ease, border-color 300ms ease, background-color 300ms ease;
}
.skill-item:hover {
  box-shadow: 0 6px 20px -8px var(--brand);
  border-color: color-mix(in oklab, var(--brand) 40%, rgba(255,255,255,0.1));
  background-color: color-mix(in oklab, var(--brand) 12%, rgba(255,255,255,0.05));
}

@media (prefers-reduced-motion: reduce) {
  .skill-item,
  .skill-title,
  .skill-logo {
    transition: none !important;
    transform: none !important;
  }
}
</style>
