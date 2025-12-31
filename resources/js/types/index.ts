/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

export interface ApiResponse<T = any> {
  code: number;
  message: string;
  data?: T;
}

export interface Pagination {
  current_page: number;
  total_pages: number;
  total_count: number;
  page_size: number;
}

export interface Reservation {
  id: number;
  reservation_code: string;
  user_id: number;
  table_id: number;
  date: string;
  time_slot: string;
  guest_count: number;
  contact_name: string;
  contact_phone: string;
  special_requests?: string;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed' | 'expired';
  expires_at?: string;
  confirmed_at?: string;
  cancelled_at?: string;
  deposit_amount?: number;
  deposit_status?: 'unpaid' | 'paid' | 'refunded' | 'forfeited';
  deposit_paid_at?: string;
  deposit_refunded_at?: string;
  deposit_transaction_id?: string;
  deposit_data?: any;
  arrived_at?: string;
  order_id?: number;
  created_at: string;
  updated_at: string;
  user?: User;
  table?: Table;
  order?: Order;
}

export interface Table {
  id: number;
  name: string;
  capacity: number;
  type: 'window' | 'corner' | 'center';
  position_x?: number | null;
  position_y?: number | null;
  default_position_x?: number | null;
  default_position_y?: number | null;
  position?: {
    x?: number;
    y?: number;
  };
  status: 'available' | 'reserved' | 'occupied' | 'maintenance';
  occupied_at?: string | null; // 使用开始时间（ISO 8601格式）
}

export interface Review {
  id: number;
  user_id: number;
  order_id: number;
  dish_id: number;
  rating: number;
  content?: string;
  images?: string[];
  tags?: string[];
  status: 'pending' | 'approved' | 'rejected';
  helpful_count: number;
  reviewed_at?: string;
  admin_reply?: string;
  admin_replied_at?: string;
  admin_replied_by?: number;
  is_adopted?: boolean;
  adopted_at?: string;
  adopted_by?: number;
  tracking_status?: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  tracking_updates?: Array<{
    action: string;
    admin_id?: number;
    admin_name?: string;
    old_status?: string;
    new_status?: string;
    message: string;
    created_at: string;
  }>;
  created_at: string;
  updated_at?: string;
  user?: User;
  dish?: Dish;
  admin_replier?: {
    id: number;
    name: string;
    username: string;
  };
  adopter?: {
    id: number;
    name: string;
    username: string;
  };
}

export interface Dish {
  id: number;
  name: string;
  description?: string;
  price: number;
  image_url?: string;
  category_id: number;
  status: 'available' | 'sold_out' | 'disabled';
  average_rating: number;
  review_count: number;
  sales_count: number;
}

export interface User {
  id: number;
  openid: string;
  nickname?: string;
  avatar_url?: string;
  phone?: string;
}

export interface Queue {
  id: number;
  queue_number: string;
  user_id: number;
  guest_count: number;
  table_type?: string;
  position: number;
  status: 'waiting' | 'called' | 'cancelled' | 'seated';
  joined_at: string;
  called_at?: string;
  seated_at?: string;
}

export interface MemberPoint {
  id: number;
  user_id: number;
  total_points: number;
  available_points: number;
  frozen_points: number;
  level: 'bronze' | 'silver' | 'gold' | 'platinum';
}

