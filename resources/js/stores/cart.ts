/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Dish } from '../types';
import type { Combo } from '../api/combo';

export interface CartItem {
  type: 'dish' | 'combo';
  dish?: Dish;
  combo?: Combo;
  quantity: number;
}

export const useCartStore = defineStore('cart', () => {
  const items = ref<CartItem[]>([]);

  // 从 localStorage 加载购物车数据
  const loadCart = () => {
    try {
      const saved = localStorage.getItem('cart');
      if (saved) {
        items.value = JSON.parse(saved);
      }
    } catch (error) {
      console.error('加载购物车失败:', error);
      items.value = [];
    }
  };

  // 保存购物车数据到 localStorage
  const saveCart = () => {
    try {
      localStorage.setItem('cart', JSON.stringify(items.value));
    } catch (error) {
      console.error('保存购物车失败:', error);
    }
  };

  // 计算总数量
  const totalQuantity = computed(() => {
    return items.value.reduce((sum, item) => sum + item.quantity, 0);
  });

  // 计算总金额
  const totalAmount = computed(() => {
    return items.value.reduce((sum, item) => {
      if (item.type === 'combo' && item.combo) {
        return sum + parseFloat(item.combo.price) * item.quantity;
      } else if (item.type === 'dish' && item.dish) {
        return sum + item.dish.price * item.quantity;
      }
      return sum;
    }, 0);
  });

  // 添加菜品到购物车
  const addDish = (dish: Dish, quantity: number = 1) => {
    const existingItem = items.value.find(item => item.type === 'dish' && item.dish?.id === dish.id);
    
    if (existingItem) {
      existingItem.quantity += quantity;
    } else {
      items.value.push({
        type: 'dish',
        dish,
        quantity,
      });
    }
    
    saveCart();
  };

  // 添加套餐到购物车
  const addCombo = (combo: Combo, quantity: number = 1) => {
    const existingItem = items.value.find(item => item.type === 'combo' && item.combo?.id === combo.id);
    
    if (existingItem) {
      existingItem.quantity += quantity;
    } else {
      items.value.push({
        type: 'combo',
        combo,
        quantity,
      });
    }
    
    saveCart();
  };

  // 更新商品数量
  const updateQuantity = (index: number, quantity: number) => {
    const item = items.value[index];
    if (item) {
      if (quantity <= 0) {
        removeItem(index);
      } else {
        item.quantity = quantity;
        saveCart();
      }
    }
  };

  // 移除商品
  const removeItem = (index: number) => {
    if (index >= 0 && index < items.value.length) {
      items.value.splice(index, 1);
      saveCart();
    }
  };

  // 清空购物车
  const clearCart = () => {
    items.value = [];
    saveCart();
  };

  // 检查菜品是否在购物车中
  const isDishInCart = (dishId: number) => {
    return items.value.some(item => item.type === 'dish' && item.dish?.id === dishId);
  };

  // 检查套餐是否在购物车中
  const isComboInCart = (comboId: number) => {
    return items.value.some(item => item.type === 'combo' && item.combo?.id === comboId);
  };

  // 获取菜品数量
  const getDishQuantity = (dishId: number) => {
    const item = items.value.find(item => item.type === 'dish' && item.dish?.id === dishId);
    return item ? item.quantity : 0;
  };

  // 获取套餐数量
  const getComboQuantity = (comboId: number) => {
    const item = items.value.find(item => item.type === 'combo' && item.combo?.id === comboId);
    return item ? item.quantity : 0;
  };

  // 初始化时加载购物车
  loadCart();

  return {
    items,
    totalQuantity,
    totalAmount,
    addDish,
    addCombo,
    updateQuantity,
    removeItem,
    clearCart,
    isDishInCart,
    isComboInCart,
    getDishQuantity,
    getComboQuantity,
    loadCart,
  };
});

