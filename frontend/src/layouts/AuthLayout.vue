<template>
  <div class="bg-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
    <div class="flex w-full max-w-sm flex-col gap-6">
      <div class="flex flex-col items-center gap-1 text-center">
        <p class="text-xl font-semibold tracking-tight">
          Amar Assist
        </p>
        <p class="text-muted-foreground text-sm">
          Acesso à plataforma
        </p>
      </div>
      <router-view />
    </div>
  </div>
</template>
