/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <section class="relative h-96 overflow-hidden">
    <!-- 轮播图容器 -->
    <div class="relative h-full overflow-hidden">
      <!-- 滑动容器 -->
      <div
        class="flex h-full transition-transform duration-700 ease-in-out"
        :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
      >
        <div
          v-for="(banner, index) in banners"
          :key="banner.id"
          class="w-full h-full flex-shrink-0 relative"
          :style="{
            backgroundImage: `url(${banner.image_url})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
          }"
        >
          <!-- 渐变遮罩 -->
          <div class="absolute inset-0 bg-gradient-to-r from-black/50 via-black/30 to-transparent"></div>
          
          <!-- 内容 -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center text-white px-4 max-w-4xl">
              <transition
                name="fade-up"
                mode="out-in"
              >
                <h1 :key="`title-${banner.id}`" class="text-5xl font-bold mb-4">{{ banner.title }}</h1>
              </transition>
              <transition
                name="fade-up"
                mode="out-in"
              >
                <p v-if="banner.description" :key="`desc-${banner.id}`" class="text-xl mb-8">{{ banner.description }}</p>
              </transition>
              <transition
                name="fade-up"
                mode="out-in"
              >
                <a
                  v-if="banner.link_url && banner.link_type !== 'none'"
                  :key="`link-${banner.id}`"
                  :href="banner.link_url"
                  :target="banner.link_type === 'external' ? '_blank' : '_self'"
                  class="bg-white text-red-600 px-8 py-3 rounded-full text-lg font-semibold hover:bg-gray-100 transition-all transform hover:scale-110 shadow-xl inline-block"
                >
                  立即查看
                </a>
                <router-link
                  v-else-if="!banner.link_url"
                  :key="`router-link-${banner.id}`"
                  to="/frontend/reservation"
                  class="bg-white text-red-600 px-8 py-3 rounded-full text-lg font-semibold hover:bg-gray-100 transition-all transform hover:scale-110 shadow-xl inline-block"
                >
                  立即预约
                </router-link>
              </transition>
            </div>
          </div>
        </div>
      </div>

      <!-- 指示器 -->
      <div v-if="banners.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2 z-10">
        <button
          v-for="(banner, index) in banners"
          :key="banner.id"
          @click="goToSlide(index)"
          class="w-3 h-3 rounded-full transition-all duration-300"
          :class="currentIndex === index ? 'bg-white w-8' : 'bg-white opacity-50 hover:opacity-75'"
          :aria-label="`切换到第${index + 1}张`"
        ></button>
      </div>

      <!-- 左右箭头 -->
      <button
        v-if="banners.length > 1"
        @click="prevSlide"
        class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-2 rounded-full transition-all z-10 backdrop-blur-sm"
        aria-label="上一张"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <button
        v-if="banners.length > 1"
        @click="nextSlide"
        class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-2 rounded-full transition-all z-10 backdrop-blur-sm"
        aria-label="下一张"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import type { Banner } from '../../api/banner';

interface Props {
  banners: Banner[];
  autoplay?: boolean;
  interval?: number;
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: true,
  interval: 5000,
});

const currentIndex = ref(0);
let autoplayTimer: number | null = null;

const goToSlide = (index: number) => {
  currentIndex.value = index;
  resetAutoplay();
};

const nextSlide = () => {
  currentIndex.value = (currentIndex.value + 1) % props.banners.length;
  resetAutoplay();
};

const prevSlide = () => {
  currentIndex.value = (currentIndex.value - 1 + props.banners.length) % props.banners.length;
  resetAutoplay();
};

const startAutoplay = () => {
  if (!props.autoplay || props.banners.length <= 1) return;
  
  autoplayTimer = window.setInterval(() => {
    nextSlide();
  }, props.interval);
};

const stopAutoplay = () => {
  if (autoplayTimer) {
    clearInterval(autoplayTimer);
    autoplayTimer = null;
  }
};

const resetAutoplay = () => {
  stopAutoplay();
  startAutoplay();
};

onMounted(() => {
  if (props.banners.length > 0) {
    startAutoplay();
  }
});

onUnmounted(() => {
  stopAutoplay();
});
</script>

<style scoped>
/* 滑动动画 */
.flex {
  will-change: transform;
}

/* 内容淡入动画 */
.fade-up-enter-active {
  transition: all 0.5s ease-out;
}

.fade-up-leave-active {
  transition: all 0.3s ease-in;
}

.fade-up-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.fade-up-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.fade-up-enter-to,
.fade-up-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>

