<template>
  <div class="min-h-screen bg-black text-white">
    <div class="mx-auto max-w-6xl px-4 py-8">
      <h1 class="text-2xl font-semibold">Admin Dashboard</h1>

      <div v-if="!token" class="mt-6 max-w-md">
        <GlassCard>
          <div class="p-6 space-y-4">
            <div class="text-sm text-white/70">Sign in with the admin password.</div>
            <input v-model="password" type="password" class="input" placeholder="Password" @keyup.enter="login" />
            <div class="flex justify-end">
              <button class="btn-primary" @click="login">Login</button>
            </div>
            <div v-if="error" class="text-red-300 text-sm">{{ error }}</div>
          </div>
        </GlassCard>
      </div>

      <div v-else class="mt-6 space-y-8">
        <div class="flex items-center justify-between">
          <div class="text-sm text-white/60">Authenticated</div>
          <button class="btn-outline" @click="logout">Logout</button>
        </div>

        <SectionKicker title="Profile" subtitle="Email & phone" />
        <GlassCard>
          <div class="p-6 space-y-3">
            <div class="grid gap-3 sm:grid-cols-2">
              <input v-model="profile.email" class="input" placeholder="Email" />
              <input v-model="profile.phone" class="input" placeholder="Phone" />
            </div>
            <div class="flex items-center justify-end gap-3">
              <span v-if="profileStatus" :class="profileStatus.ok ? 'text-sm text-emerald-400' : 'text-sm text-red-400'">
                {{ profileStatus.text }}
              </span>
              <button class="btn-primary" @click="saveProfile">Save Profile</button>
            </div>
          </div>
        </GlassCard>

        <SectionKicker title="CV" subtitle="Upload or replace the downloadable PDF" />
        <GlassCard>
          <div class="p-6 space-y-3">
            <div class="text-sm text-white/70">
              <span v-if="cv.available">
                Current: {{ cv.filename }}
                <span v-if="cv.updated_at" class="text-white/50">
                  (updated {{ new Date(cv.updated_at).toLocaleDateString() }})
                </span>
              </span>
              <span v-else>No CV uploaded yet.</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <input ref="cvInput" type="file" accept="application/pdf" class="input" @change="onCvPicked" />
              <button class="btn-primary" :disabled="!cvFile || cvBusy" @click="uploadCv">
                {{ cvBusy ? 'Uploading...' : 'Upload' }}
              </button>
              <a v-if="cv.available" class="btn-outline" :href="cvUrl" target="_blank" rel="noopener">Download</a>
            </div>
            <div v-if="cvError" class="text-sm text-red-400">{{ cvError }}</div>
          </div>
        </GlassCard>

        <SectionKicker title="Skills" subtitle="Add or remove skills" />
        <GlassCard>
          <div class="p-6 space-y-3">
            <div class="grid gap-2 sm:grid-cols-[1fr_auto_auto_auto]">
              <input v-model="newSkill" class="input" placeholder="New skill" @keyup.enter="addSkill" />
              <select v-model="newSkillCategory" class="input">
                <option v-for="c in SKILL_CATEGORIES" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
              <select v-model="newSkillLevel" class="input">
                <option v-for="l in SKILL_LEVELS" :key="l.value" :value="l.value">{{ l.label }}</option>
              </select>
              <button class="btn-primary" @click="addSkill">Add</button>
            </div>
            <div class="mt-3 space-y-2">
              <div
                v-for="(s, i) in skills"
                :key="s.name"
                class="grid items-center gap-2 rounded-xl border border-white/10 p-2 sm:grid-cols-[1fr_auto_auto_auto]"
              >
                <span class="text-sm">{{ s.name }}</span>
                <select v-model="s.category" class="input">
                  <option v-for="c in SKILL_CATEGORIES" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <select v-model="s.level" class="input">
                  <option v-for="l in SKILL_LEVELS" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
                <button class="btn-icon" @click="removeSkill(i)">×</button>
              </div>
            </div>
            <div class="flex justify-end">
              <span v-if="skillsStatus" :class="skillsStatus.ok ? 'text-sm text-emerald-400' : 'text-sm text-red-400'">
                {{ skillsStatus.text }}
              </span>
              <button class="btn-outline" @click="saveSkills">Save Skills</button>
            </div>
          </div>
        </GlassCard>

        <SectionKicker title="Projects" subtitle="Create, edit or remove" />
        <GlassCard>
          <div class="p-6 space-y-4">
            <details class="rounded-xl border border-white/10">
              <summary class="px-4 py-2 cursor-pointer">Add Project</summary>
              <div class="space-y-4 p-4">
                <div class="grid gap-3 sm:grid-cols-2">
                  <div>
                    <label class="field-label">Slug <span class="text-white/30">(unique, lowercase)</span></label>
                    <input v-model="draft.id" class="input" placeholder="my-project" />
                  </div>
                  <div>
                    <label class="field-label">Title</label>
                    <input v-model="draft.title" class="input" placeholder="My Project" />
                  </div>
                </div>
                <div>
                  <label class="field-label">Tagline</label>
                  <input v-model="draft.tagline" class="input" placeholder="One line that sells it" />
                </div>
                <div>
                  <label class="field-label">Summary</label>
                  <textarea v-model="draft.summary" class="textarea" placeholder="What it is and why it matters"></textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                  <div>
                    <label class="field-label">Year</label>
                    <input v-model="draft.year" class="input" placeholder="2026" />
                  </div>
                  <div>
                    <label class="field-label">Category</label>
                    <select v-model="draft.category" class="input">
                    <option value="Backend">Backend</option>
                    <option value="AI">AI</option>
                    <option value="Enterprise">Enterprise</option>
                    <option value="IoT">IoT</option>
                    </select>
                  </div>
                  <div>
                    <label class="field-label">Highlights <span class="text-white/30">(one per line)</span></label>
                    <textarea v-model="stackText" class="textarea min-h-[90px]" placeholder="Built a thing, then another thing&#10;Second bullet point"></textarea>
                  </div>
                </div>
                <div>
                  <label class="field-label">Repository URL</label>
                  <input v-model="draft.repoUrl" class="input" type="url" placeholder="https://github.com/user/repo" />
                  <p class="mt-1 text-xs text-white/40">Shown on the project card as a "Repo" link. Leave empty for none.</p>
                </div>
                <div v-if="pError" class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-300">{{ pError }}</div>
                <div class="flex justify-end gap-2 border-t border-white/10 pt-3">
                  <button class="btn-primary" @click="createProject">Create project</button>
                </div>
              </div>
            </details>

            <div class="space-y-3">
              <div
                v-for="p in projects"
                :key="p.slug"
                class="rounded-xl border p-3 transition-colors"
                :class="editingId === p.slug ? 'border-white/30 bg-white/[0.04]' : 'border-white/10 hover:border-white/20'"
              >
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="min-w-0">
                    <div class="truncate font-medium">{{ p.title }}</div>
                    <div class="truncate text-xs text-white/40">{{ p.slug }} · {{ p.category }} · {{ p.year }}</div>
                  </div>
                  <div class="flex shrink-0 gap-2">
                    <button class="btn-outline" @click="startEdit(p)">
                      {{ editingId === p.slug ? 'Editing' : 'Edit' }}
                    </button>
                    <button class="btn-danger" @click="delProject(p.slug)">Delete</button>
                  </div>
                </div>

                <div v-if="editingId === p.slug" class="mt-4 space-y-4 border-t border-white/10 pt-4">
                  <div>
                    <label class="field-label">Title</label>
                    <input v-model="editBuf.title" class="input" placeholder="Title" />
                  </div>
                  <div>
                    <label class="field-label">Tagline</label>
                    <input v-model="editBuf.tagline" class="input" placeholder="Tagline" />
                  </div>
                  <div>
                    <label class="field-label">Summary</label>
                    <textarea v-model="editBuf.summary" class="textarea" placeholder="Summary"></textarea>
                  </div>
                  <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                      <label class="field-label">Year</label>
                      <input v-model="editBuf.year" class="input" placeholder="Year" />
                    </div>
                    <div>
                      <label class="field-label">Category</label>
                      <select v-model="editBuf.category" class="input">
                      <option value="Backend">Backend</option>
                      <option value="AI">AI</option>
                      <option value="Enterprise">Enterprise</option>
                      <option value="IoT">IoT</option>
                      </select>
                    </div>
                    <div>
                      <label class="field-label">Highlights <span class="text-white/30">(one per line)</span></label>
                      <textarea v-model="editStackText" class="textarea min-h-[90px]" placeholder="One bullet per line"></textarea>
                    </div>
                  </div>
                  <div>
                    <label class="field-label">Repository URL</label>
                    <input v-model="editRepoUrl" class="input" type="url" placeholder="https://github.com/user/repo" />
                  </div>
                  <div v-if="editError" class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-300">{{ editError }}</div>
                  <div class="flex justify-end gap-2 border-t border-white/10 pt-3">
                    <button class="btn-ghost" @click="cancelEdit">Cancel</button>
                    <button class="btn-primary" @click="saveEdit(p.slug)">Save changes</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </GlassCard>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import GlassCard from './GlassCard.vue'
import SectionKicker from './SectionKicker.vue'
import { adminLogin, adminFetch, setToken, getToken, adminUpdateProfile, adminSetSkills, adminAddProject, adminDeleteProject, adminUpdateProject, adminUploadCv } from '../api/admin'
import type { AdminSkill } from '../api/admin'
import { apiUrl } from '../api/http'

const props = defineProps<{ isLight: boolean }>()
defineEmits<{ (e: 'toggle-theme'): void }>()

const token = ref<string | null>(null)
const password = ref('')
const error = ref('')

const profile = ref<{ email: string; phone?: string }>({ email: '', phone: '' })
// Must match App\Enum\SkillCategory / App\Enum\SkillLevel on the backend.
const SKILL_CATEGORIES = [
  { value: 'frontend', label: 'Frontend' },
  { value: 'backend', label: 'Backend' },
  { value: 'data_ai', label: 'Data / AI' },
  { value: 'devops', label: 'DevOps' },
]
const SKILL_LEVELS = [
  { value: 'beginner', label: 'Beginner' },
  { value: 'intermediate', label: 'Intermediate' },
  { value: 'advanced', label: 'Advanced' },
]

const skills = ref<AdminSkill[]>([])
const newSkill = ref('')
const newSkillCategory = ref('backend')
const newSkillLevel = ref('advanced')
const projects = ref<any[]>([])

const cv = ref<{ available: boolean; filename: string | null; updated_at: string | null }>({
  available: false, filename: null, updated_at: null,
})
const cvFile = ref<File | null>(null)
const cvBusy = ref(false)
const cvError = ref('')
const cvInput = ref<HTMLInputElement | null>(null)
const cvUrl = apiUrl('/api/cv')

function onCvPicked(e: Event) {
  const input = e.target as HTMLInputElement
  cvFile.value = input.files?.[0] ?? null
  cvError.value = ''
}

async function uploadCv() {
  if (!cvFile.value) return
  cvBusy.value = true
  cvError.value = ''
  try {
    await adminUploadCv(cvFile.value)
    cvFile.value = null
    if (cvInput.value) cvInput.value.value = ''
    await refresh()
  } catch (e: any) {
    cvError.value = e?.message || 'Upload failed'
  } finally {
    cvBusy.value = false
  }
}

const emptyDraft = () => ({
  id: '', title: '', tagline: '', summary: '', year: '', category: 'Backend', stack: [], repoUrl: '',
})
const draft = ref<any>(emptyDraft())
const stackText = ref('')

const editingId = ref<string | null>(null)
const editBuf = ref<any>({})
const editStackText = ref('')
const editRepoUrl = ref('')
const editError = ref('')

// The API models links as [{ label, href }]. The admin exposes just the
// repository, so these translate between the two without discarding any other
// link that may already be stored on the project.
const REPO_LABEL = 'Repo'

// Highlights are bullet sentences that routinely contain commas, so they are
// separated by newlines. Splitting on commas silently shredded one bullet into
// several.
function splitBullets(text: string): string[] {
  return text.split('\n').map((s) => s.trim()).filter(Boolean)
}

function repoFromLinks(links: unknown): string {
  if (!Array.isArray(links)) return ''
  const found = links.find(
    (l: any) => l && typeof l === 'object' && String(l.label).toLowerCase() === REPO_LABEL.toLowerCase(),
  )
  return found?.href ?? ''
}

function linksWithRepo(existing: unknown, url: string) {
  const others = Array.isArray(existing)
    ? existing.filter(
        (l: any) => l && typeof l === 'object' && String(l.label).toLowerCase() !== REPO_LABEL.toLowerCase(),
      )
    : []
  const href = url.trim()
  return href ? [...others, { label: REPO_LABEL, href }] : others
}

async function login() {
  try {
    error.value = ''
    const t = await adminLogin(password.value)
    token.value = t
    await refresh()
  } catch (e: any) {
    error.value = e?.message || 'Login failed'
  }
}

function logout() {
  setToken(null)
  token.value = null
}

async function refresh() {
  const data = await adminFetch()
  profile.value = data.profile || { email: '', phone: '' }
  skills.value = data.skills || []
  projects.value = data.projects || []
  cv.value = data.cv || { available: false, filename: null, updated_at: null }
}

// Both saves previously awaited the request with no try/catch, so a failure
// became an unhandled rejection: the UI looked identical whether the save
// succeeded or not.
const profileStatus = ref<{ ok: boolean; text: string } | null>(null)

async function saveProfile() {
  profileStatus.value = null
  try {
    await adminUpdateProfile(profile.value)
    profileStatus.value = { ok: true, text: 'Profile saved' }
  } catch (e: any) {
    profileStatus.value = { ok: false, text: e?.message || 'Failed to save the profile' }
  }
}

function addSkill() {
  const name = newSkill.value.trim()
  if (!name) return
  if (!skills.value.some((s) => s.name === name)) {
    skills.value.push({ name, category: newSkillCategory.value, level: newSkillLevel.value })
  }
  newSkill.value = ''
}
function removeSkill(i: number) { skills.value.splice(i, 1) }
const skillsStatus = ref<{ ok: boolean; text: string } | null>(null)

async function saveSkills() {
  skillsStatus.value = null
  try {
    skills.value = await adminSetSkills(skills.value)
    skillsStatus.value = { ok: true, text: 'Skills saved' }
  } catch (e: any) {
    skillsStatus.value = { ok: false, text: e?.message || 'Failed to save the skills' }
  }
}

async function createProject() {
  try {
    // repoUrl is an admin-only convenience field; the API expects `links`.
    const { repoUrl, ...payload } = draft.value
    payload.stack = splitBullets(stackText.value)
    payload.links = linksWithRepo([], repoUrl ?? '')

    await adminAddProject(payload)
    stackText.value = ''
    draft.value = emptyDraft()
    await refresh()
    pError.value = ''
  } catch (e: any) {
    pError.value = e?.message || 'Failed to create project'
  }
}

const pError = ref('')

function startEdit(p: any) {
  editingId.value = p.slug
  editBuf.value = { ...p }
  // The API returns the stack as `highlights`; `p.stack` is always undefined,
  // which is why this field came up blank.
  editStackText.value = Array.isArray(p.highlights) ? p.highlights.join('\n') : ''
  editRepoUrl.value = repoFromLinks(p.links)
  editError.value = ''
}
function cancelEdit() { editingId.value = null }
async function saveEdit(id: string) {
  editError.value = ''
  try {
    const { slug, highlights, links, ...rest } = editBuf.value
    const patch = {
      ...rest,
      stack: splitBullets(editStackText.value),
      links: linksWithRepo(links, editRepoUrl.value),
    }
    await adminUpdateProject(id, patch)
    editingId.value = null
    await refresh()
  } catch (e: any) {
    editError.value = e?.message || 'Failed to save the project'
  }
}
async function delProject(id: string) {
  if (!confirm(`Delete the project "${id}"? This cannot be undone.`)) return
  try {
    await adminDeleteProject(id)
    await refresh()
  } catch (e: any) {
    pError.value = e?.message || 'Failed to delete the project'
  }
}


onMounted(async () => {
  token.value = getToken()
  if (token.value) await refresh()
})
</script>

<style scoped>
</style>
