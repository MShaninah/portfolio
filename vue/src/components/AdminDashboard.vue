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
            <div class="flex justify-end">
              <button class="btn-primary" @click="saveProfile">Save Profile</button>
            </div>
          </div>
        </GlassCard>

        <SectionKicker title="Skills" subtitle="Add or remove skills" />
        <GlassCard>
          <div class="p-6 space-y-3">
            <div class="flex gap-2">
              <input v-model="newSkill" class="input" placeholder="New skill" @keyup.enter="addSkill" />
              <button class="btn-primary" @click="addSkill">Add</button>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span v-for="(s, i) in skills" :key="s" class="badge-secondary inline-flex items-center gap-2">
                {{ s }}
                <button class="btn-icon" @click="removeSkill(i)">×</button>
              </span>
            </div>
            <div class="flex justify-end">
              <button class="btn-outline" @click="saveSkills">Save Skills</button>
            </div>
          </div>
        </GlassCard>

        <SectionKicker title="Projects" subtitle="Create, edit or remove" />
        <GlassCard>
          <div class="p-6 space-y-4">
            <details class="rounded-xl border border-white/10">
              <summary class="px-4 py-2 cursor-pointer">Add Project</summary>
              <div class="p-4 space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                  <input v-model="draft.id" class="input" placeholder="ID (unique)" />
                  <input v-model="draft.title" class="input" placeholder="Title" />
                </div>
                <input v-model="draft.tagline" class="input" placeholder="Tagline" />
                <textarea v-model="draft.summary" class="textarea" placeholder="Summary"></textarea>
                <div class="grid gap-3 sm:grid-cols-3">
                  <input v-model="draft.year" class="input" placeholder="Year" />
                  <select v-model="draft.category" class="input">
                    <option>Backend</option>
                    <option>AI</option>
                    <option>Enterprise</option>
                    <option>IoT</option>
                  </select>
                  <input v-model="stackText" class="input" placeholder="Stack (comma separated)" />
                </div>
                <div class="flex justify-end">
                  <button class="btn-primary" @click="createProject">Create</button>
                </div>
                <div v-if="pError" class="text-red-300 text-sm">{{ pError }}</div>
              </div>
            </details>

            <div class="space-y-3">
              <div v-for="p in projects" :key="p.id" class="rounded-xl border border-white/10 p-3">
                <div class="flex items-center justify-between">
                  <div class="font-medium">{{ p.title }} <span class="text-white/50">({{ p.id }})</span></div>
                  <div class="flex gap-2">
                    <button class="btn-outline" @click="startEdit(p)">Edit</button>
                    <button class="btn-outline" @click="delProject(p.id)">Delete</button>
                  </div>
                </div>
                <div v-if="editingId === p.id" class="mt-3 space-y-2">
                  <input v-model="editBuf.title" class="input" placeholder="Title" />
                  <input v-model="editBuf.tagline" class="input" placeholder="Tagline" />
                  <textarea v-model="editBuf.summary" class="textarea" placeholder="Summary"></textarea>
                  <div class="grid gap-2 sm:grid-cols-3">
                    <input v-model="editBuf.year" class="input" placeholder="Year" />
                    <select v-model="editBuf.category" class="input">
                      <option>Backend</option>
                      <option>AI</option>
                      <option>Enterprise</option>
                      <option>IoT</option>
                    </select>
                    <input v-model="editStackText" class="input" placeholder="Stack (comma)" />
                  </div>
                  <div class="flex justify-end gap-2">
                    <button class="btn-outline" @click="cancelEdit">Cancel</button>
                    <button class="btn-primary" @click="saveEdit(p.id)">Save</button>
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
import { adminLogin, adminFetch, setToken, getToken, adminUpdateProfile, adminSetSkills, adminAddProject, adminDeleteProject, adminUpdateProject } from '../api/admin'

const props = defineProps<{ isLight: boolean }>()
defineEmits<{ (e: 'toggle-theme'): void }>()

const token = ref<string | null>(null)
const password = ref('')
const error = ref('')

const profile = ref<{ email: string; phone?: string }>({ email: '', phone: '' })
const skills = ref<string[]>([])
const newSkill = ref('')
const projects = ref<any[]>([])

const draft = ref<any>({ id: '', title: '', tagline: '', summary: '', year: '', category: 'Backend', stack: [] })
const stackText = ref('')

const editingId = ref<string | null>(null)
const editBuf = ref<any>({})
const editStackText = ref('')

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
}

async function saveProfile() {
  await adminUpdateProfile(profile.value)
}

function addSkill() {
  const s = newSkill.value.trim()
  if (!s) return
  if (!skills.value.includes(s)) skills.value.push(s)
  newSkill.value = ''
}
function removeSkill(i: number) { skills.value.splice(i, 1) }
async function saveSkills() { skills.value = await adminSetSkills(skills.value) }

async function createProject() {
  try {
    draft.value.stack = stackText.value.split(',').map(s => s.trim()).filter(Boolean)
    await adminAddProject(draft.value)
    stackText.value = ''
    draft.value = { id: '', title: '', tagline: '', summary: '', year: '', category: 'Backend', stack: [] }
    await refresh()
    pError.value = ''
  } catch (e: any) {
    pError.value = e?.message || 'Failed to create project'
  }
}

const pError = ref('')

function startEdit(p: any) {
  editingId.value = p.id
  editBuf.value = { ...p }
  editStackText.value = Array.isArray(p.stack) ? p.stack.join(', ') : ''
}
function cancelEdit() { editingId.value = null }
async function saveEdit(id: string) {
  const patch = { ...editBuf.value, stack: editStackText.value.split(',').map((s:string)=>s.trim()).filter(Boolean) }
  await adminUpdateProject(id, patch)
  editingId.value = null
  await refresh()
}
async function delProject(id: string) {
  if (!confirm('Delete project?')) return
  await adminDeleteProject(id)
  await refresh()
}

function go(id: string) {
  // in admin we can simply scroll or ignore
}

onMounted(async () => {
  token.value = getToken()
  if (token.value) await refresh()
})
</script>

<style scoped>
</style>
