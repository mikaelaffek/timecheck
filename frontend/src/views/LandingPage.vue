<template>
  <div class="landing-page">
    <!-- Hero Section -->
    <div class="hero-section">
      <v-container>
        <v-row align="center" justify="space-between" class="py-16">
          <v-col cols="12" md="7">
            <h1 class="text-h2 font-weight-bold mb-6 white--text">
              Schemaläggning, tid och lön, smidigt och effektivt
            </h1>
            <h2 class="text-h5 mb-8 white--text">
              Spara tid och pengar med smarta funktioner för din personalhantering
            </h2>
            <v-btn
              color="white"
              size="x-large"
              class="mr-4 text-primary"
              elevation="2"
              @click="scrollToFeatures"
            >
              Utforska funktioner
            </v-btn>
            <v-btn
              color="primary"
              size="x-large"
              class="white--text"
              elevation="2"
              @click="goToLogin"
            >
              Kom igång
            </v-btn>
          </v-col>
          <v-col cols="12" md="5" class="d-none d-md-flex justify-center">
            <v-img
              src="/landing-illustration.svg"
              max-width="400"
              alt="Timetjek Illustration"
            ></v-img>
          </v-col>
        </v-row>
      </v-container>
    </div>

    <!-- Features Section -->
    <div id="features" class="features-section py-16">
      <v-container>
        <h2 class="text-h4 text-center font-weight-bold mb-12">
          Kraftfulla funktioner för moderna arbetsplatser
        </h2>
        <v-row>
          <v-col cols="12" md="4">
            <v-card class="feature-card h-100" elevation="2">
              <v-card-title class="d-flex align-center">
                <v-icon color="primary" size="36" class="mr-3">mdi-calendar-check</v-icon>
                Schemaläggning
              </v-card-title>
              <v-card-text>
                <p>Skapa och hantera arbetsscheman enkelt. Automatisera återkommande scheman och låt anställda se sina arbetstider direkt i appen.</p>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card class="feature-card h-100" elevation="2">
              <v-card-title class="d-flex align-center">
                <v-icon color="primary" size="36" class="mr-3">mdi-clock-outline</v-icon>
                Tidrapportering
              </v-card-title>
              <v-card-text>
                <p>Stämpla in och ut direkt från mobilen. Spåra arbetstid automatiskt och få detaljerade rapporter över arbetade timmar.</p>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card class="feature-card h-100" elevation="2">
              <v-card-title class="d-flex align-center">
                <v-icon color="primary" size="36" class="mr-3">mdi-chart-bar</v-icon>
                Rapporter & Analys
              </v-card-title>
              <v-card-text>
                <p>Få insikt i arbetstider, kostnader och effektivitet med kraftfulla rapporter och visualiseringar.</p>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </div>

    <!-- Testimonials Section -->
    <div class="testimonials-section py-16 grey lighten-4">
      <v-container>
        <h2 class="text-h4 text-center font-weight-bold mb-12">
          Vad våra kunder säger
        </h2>
        <v-row>
          <v-col cols="12" md="4">
            <v-card class="testimonial-card h-100" elevation="1">
              <v-card-text>
                <p class="text-body-1 font-italic mb-4">
                  "Timetjek har revolutionerat vår personalhantering. Vi sparar timmar varje vecka på administration."
                </p>
                <div class="d-flex align-center">
                  <v-avatar color="primary" class="mr-3">
                    <span class="white--text">AB</span>
                  </v-avatar>
                  <div>
                    <div class="font-weight-bold">Anna Bergström</div>
                    <div class="text-caption">HR-chef, Svea Bygg AB</div>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card class="testimonial-card h-100" elevation="1">
              <v-card-text>
                <p class="text-body-1 font-italic mb-4">
                  "Enkelt att använda och fantastisk kundservice. Våra anställda älskar att kunna se sina scheman direkt i mobilen."
                </p>
                <div class="d-flex align-center">
                  <v-avatar color="primary" class="mr-3">
                    <span class="white--text">ML</span>
                  </v-avatar>
                  <div>
                    <div class="font-weight-bold">Magnus Lindström</div>
                    <div class="text-caption">Verksamhetschef, Café Stockholm</div>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card class="testimonial-card h-100" elevation="1">
              <v-card-text>
                <p class="text-body-1 font-italic mb-4">
                  "Rapporterna hjälper oss att optimera bemanningen och spara kostnader. Bästa investeringen vi gjort i år!"
                </p>
                <div class="d-flex align-center">
                  <v-avatar color="primary" class="mr-3">
                    <span class="white--text">KJ</span>
                  </v-avatar>
                  <div>
                    <div class="font-weight-bold">Karin Johansson</div>
                    <div class="text-caption">Ekonomichef, Nordic Health</div>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </div>

    <!-- CTA Section -->
    <div class="cta-section py-16 text-center">
      <v-container>
        <h2 class="text-h3 font-weight-bold mb-6">
          Redo att förenkla din personalhantering?
        </h2>
        <p class="text-h6 mb-8 grey--text text--darken-1">
          Börja använda Timetjek idag och se skillnaden direkt.
        </p>
        <v-btn
          color="primary"
          x-large
          class="white--text px-8"
          elevation="2"
          @click="goToLogin"
        >
          Kom igång nu
        </v-btn>
      </v-container>
    </div>

    <!-- Footer -->
    <v-footer color="primary" dark>
      <v-container>
        <v-row>
          <v-col cols="12" md="4">
            <div class="d-flex align-center mb-4">
              <img src="/logo-green.svg" alt="Timetjek Logo" height="40" class="mr-2" />
            </div>
            <p class="mb-0">
              Schemaläggning, tid och lön, smidigt och effektivt.
            </p>
          </v-col>
          <v-col cols="12" md="4">
            <h3 class="text-h6 mb-4">Kontakt</h3>
            <p>
              <v-icon small class="mr-2">mdi-email</v-icon> info@timetjek.se
            </p>
            <p>
              <v-icon small class="mr-2">mdi-phone</v-icon> 08-123 45 67
            </p>
            <p>
              <v-icon small class="mr-2">mdi-map-marker</v-icon> Storgatan 1, 111 23 Stockholm
            </p>
          </v-col>
          <v-col cols="12" md="4">
            <h3 class="text-h6 mb-4">Följ oss</h3>
            <div class="d-flex">
              <v-btn icon class="mr-2">
                <v-icon>mdi-facebook</v-icon>
              </v-btn>
              <v-btn icon class="mr-2">
                <v-icon>mdi-twitter-x</v-icon>
              </v-btn>
              <v-btn icon class="mr-2">
                <v-icon>mdi-linkedin</v-icon>
              </v-btn>
              <v-btn icon>
                <v-icon>mdi-instagram</v-icon>
              </v-btn>
            </div>
          </v-col>
        </v-row>
        <v-divider class="my-4 white"></v-divider>
        <div class="text-center">
          &copy; {{ new Date().getFullYear() }} Timetjek. Alla rättigheter förbehållna.
        </div>
      </v-container>
    </v-footer>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useRouter } from 'vue-router'

export default defineComponent({
  name: 'LandingPage',
  setup() {
    const router = useRouter()

    const goToLogin = () => {
      router.push('/login')
    }

    const scrollToFeatures = () => {
      const featuresElement = document.getElementById('features')
      if (featuresElement) {
        featuresElement.scrollIntoView({ behavior: 'smooth' })
      }
    }

    return {
      goToLogin,
      scrollToFeatures
    }
  }
})
</script>

<style scoped>
.landing-page {
  background-color: #ffffff;
}

.hero-section {
  background: linear-gradient(135deg, #1C3A2A 0%, #2A5A40 100%);
  min-height: 80vh;
  display: flex;
  align-items: center;
}

.feature-card, .testimonial-card {
  transition: transform 0.3s ease;
  height: 100%;
}

.feature-card:hover, .testimonial-card:hover {
  transform: translateY(-5px);
}

.cta-section {
  background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
}
</style>
