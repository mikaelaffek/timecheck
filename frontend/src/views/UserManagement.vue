<template>
  <div>
    <v-row>
      <v-col cols="12" class="d-flex align-center">
        <h1 class="text-h4">User Management</h1>
        <v-spacer></v-spacer>
        <v-btn color="primary" @click="openUserDialog()">
          <v-icon left>mdi-account-plus</v-icon>
          Add User
        </v-btn>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
            ></v-text-field>
          </v-card-title>
          <v-data-table
            :headers="headers"
            :items="users"
            :search="search"
            :loading="loading"
            class="elevation-1"
          >
            <template v-slot:item.role="{ item }">
              <v-chip
                :color="getRoleColor(item.role)"
                text-color="white"
                small
              >
                {{ item.role }}
              </v-chip>
            </template>
            <template v-slot:item.actions="{ item }">
              <v-icon small class="mr-2" @click="openUserDialog(item)">
                mdi-pencil
              </v-icon>
              <v-icon small @click="confirmDeleteUser(item)">
                mdi-delete
              </v-icon>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>

    <!-- User Dialog -->
    <v-dialog v-model="userDialog" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ editMode ? 'Edit User' : 'Add User' }}</span>
        </v-card-title>
        <v-card-text>
          <v-form ref="form" v-model="valid">
            <v-text-field
              v-model="editedUser.personal_id"
              label="Personal ID"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-text-field
              v-model="editedUser.name"
              label="Full Name"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-text-field
              v-model="editedUser.email"
              label="Email"
              :rules="[rules.required, rules.email]"
              required
            ></v-text-field>
            <v-select
              v-model="editedUser.role"
              :items="roles"
              label="Role"
              :rules="[rules.required]"
              required
            ></v-select>
            <v-select
              v-model="editedUser.department_id"
              :items="departments"
              item-title="name"
              item-value="id"
              label="Department"
            ></v-select>
            <v-text-field
              v-if="!editMode"
              v-model="editedUser.password"
              label="Password"
              type="password"
              :rules="[rules.required, rules.minLength]"
              required
            ></v-text-field>
            <v-text-field
              v-if="!editMode"
              v-model="editedUser.password_confirmation"
              label="Confirm Password"
              type="password"
              :rules="[rules.required, rules.passwordMatch]"
              required
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue-darken-1" variant="text" @click="userDialog = false">
            Cancel
          </v-btn>
          <v-btn color="primary" :disabled="!valid" @click="saveUser">
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">
          Confirm Delete
        </v-card-title>
        <v-card-text>
          Are you sure you want to delete this user? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue-darken-1" variant="text" @click="deleteDialog = false">
            Cancel
          </v-btn>
          <v-btn color="error" @click="deleteUser">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, reactive, onMounted } from 'vue'
import axios from 'axios'

interface User {
  id: number
  personal_id: string
  name: string
  email: string
  role: string
  department_id: number | null
  password?: string
  password_confirmation?: string
}

interface Department {
  id: number
  name: string
}

export default defineComponent({
  name: 'UserManagement',
  setup() {
    const loading = ref(false)
    const search = ref('')
    const users = ref<User[]>([])
    const departments = ref<Department[]>([])
    const userDialog = ref(false)
    const deleteDialog = ref(false)
    const editMode = ref(false)
    const valid = ref(false)
    const userToDelete = ref<User | null>(null)
    
    const roles = ['admin', 'manager', 'employee']
    
    const headers = [
      { title: 'Personal ID', key: 'personal_id' },
      { title: 'Name', key: 'name' },
      { title: 'Email', key: 'email' },
      { title: 'Role', key: 'role' },
      { title: 'Actions', key: 'actions', sortable: false }
    ]
    
    const defaultUser: User = {
      id: 0,
      personal_id: '',
      name: '',
      email: '',
      role: 'employee',
      department_id: null,
      password: '',
      password_confirmation: ''
    }
    
    const editedUser = reactive<User>({ ...defaultUser })
    
    const rules = {
      required: (v: string) => !!v || 'This field is required',
      email: (v: string) => {
        const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        return pattern.test(v) || 'Invalid email address'
      },
      minLength: (v: string) => v.length >= 8 || 'Password must be at least 8 characters',
      passwordMatch: (v: string) => v === editedUser.password || 'Passwords do not match'
    }
    
    const fetchUsers = async () => {
      loading.value = true
      try {
        const response = await axios.get('/api/users')
        users.value = response.data
      } catch (error) {
        console.error('Error fetching users:', error)
      } finally {
        loading.value = false
      }
    }
    
    const fetchDepartments = async () => {
      try {
        const response = await axios.get('/api/departments')
        departments.value = response.data
      } catch (error) {
        console.error('Error fetching departments:', error)
      }
    }
    
    const getRoleColor = (role: string) => {
      switch (role) {
        case 'admin':
          return 'red'
        case 'manager':
          return 'orange'
        default:
          return 'green'
      }
    }
    
    const openUserDialog = (user: User | null = null) => {
      editMode.value = !!user
      
      if (user) {
        // Clone the user object to avoid modifying the original
        Object.assign(editedUser, user)
      } else {
        // Reset to default values for new user
        Object.assign(editedUser, defaultUser)
      }
      
      userDialog.value = true
    }
    
    const saveUser = async () => {
      try {
        if (editMode.value) {
          // Update existing user
          await axios.put(`/api/users/${editedUser.id}`, {
            personal_id: editedUser.personal_id,
            name: editedUser.name,
            email: editedUser.email,
            role: editedUser.role,
            department_id: editedUser.department_id
          })
        } else {
          // Create new user
          await axios.post('/api/users', editedUser)
        }
        
        userDialog.value = false
        fetchUsers() // Refresh the user list
      } catch (error) {
        console.error('Error saving user:', error)
      }
    }
    
    const confirmDeleteUser = (user: User) => {
      userToDelete.value = user
      deleteDialog.value = true
    }
    
    const deleteUser = async () => {
      if (!userToDelete.value) return
      
      try {
        await axios.delete(`/api/users/${userToDelete.value.id}`)
        deleteDialog.value = false
        fetchUsers() // Refresh the user list
      } catch (error) {
        console.error('Error deleting user:', error)
      }
    }
    
    onMounted(() => {
      fetchUsers()
      fetchDepartments()
    })
    
    return {
      loading,
      search,
      users,
      departments,
      headers,
      roles,
      userDialog,
      deleteDialog,
      editMode,
      valid,
      editedUser,
      rules,
      getRoleColor,
      openUserDialog,
      saveUser,
      confirmDeleteUser,
      deleteUser
    }
  }
})
</script>
