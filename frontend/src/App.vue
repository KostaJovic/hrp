<template>
  <v-app>
    <v-app-bar title="HRP">
      <v-btn to="/">Home</v-btn>
      <v-btn to="/about">About</v-btn>
    </v-app-bar>

    <v-main>
      <v-container>
        <RouterView />
      </v-container>
    </v-main>
  </v-app>
</template>
