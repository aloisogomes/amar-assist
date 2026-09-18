<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ApiError } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import type { ValidationErrors } from '@/types/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})
const errors = ref<ValidationErrors>({})
const submitting = ref(false)
const generalError = ref('')

async function onSubmit() {
  errors.value = {}
  generalError.value = ''
  submitting.value = true

  try {
    await auth.register({
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    await router.push({ name: 'home' })
  }
  catch (error) {
    if (error instanceof ApiError) {
      errors.value = error.errors
      if (!Object.keys(error.errors).length) {
        generalError.value = error.message
      }
    }
    else {
      generalError.value = 'Não foi possível criar a conta. Tente novamente.'
    }
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Criar conta</CardTitle>
      <CardDescription>
        Preencha seus dados para cadastrar um novo usuário.
      </CardDescription>
    </CardHeader>
    <CardContent>
      <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
        <p v-if="generalError" class="text-destructive text-sm">
          {{ generalError }}
        </p>
        <div class="flex flex-col gap-2">
          <Label for="name">Nome</Label>
          <Input
            id="name"
            v-model="form.name"
            type="text"
            autocomplete="name"
            required
            :aria-invalid="Boolean(errors.name)"
          />
          <p v-if="errors.name" class="text-destructive text-sm">
            {{ errors.name[0] }}
          </p>
        </div>
        <div class="flex flex-col gap-2">
          <Label for="email">E-mail</Label>
          <Input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            required
            :aria-invalid="Boolean(errors.email)"
          />
          <p v-if="errors.email" class="text-destructive text-sm">
            {{ errors.email[0] }}
          </p>
        </div>
        <div class="flex flex-col gap-2">
          <Label for="password">Senha</Label>
          <Input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            required
            :aria-invalid="Boolean(errors.password)"
          />
          <p v-if="errors.password" class="text-destructive text-sm">
            {{ errors.password[0] }}
          </p>
        </div>
        <div class="flex flex-col gap-2">
          <Label for="password_confirmation">Confirmar senha</Label>
          <Input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
          />
        </div>
        <Button type="submit" :disabled="submitting">
          {{ submitting ? 'Cadastrando...' : 'Cadastrar' }}
        </Button>
        <p class="text-muted-foreground text-center text-sm">
          Já tem uma conta?
          <RouterLink to="/login" class="text-foreground underline-offset-4 hover:underline">
            Entrar
          </RouterLink>
        </p>
      </form>
    </CardContent>
  </Card>
</template>
