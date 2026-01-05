/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface CheckinStat {
  total_days: number;
  current_consecutive_days: number;
  max_consecutive_days: number;
  last_checkin_date: string | null;
  is_checked_today: boolean;
  today_reward_points: number | null;
  makeup_count: number;
}

export interface CheckinCalendar {
  year: number;
  month: number;
  calendar: Array<{
    date: string;
    day: number;
    is_checked: boolean;
    is_today: boolean;
    is_past: boolean;
    is_future: boolean;
    consecutive_days: number | null;
    reward_points: number | null;
    is_makeup: boolean;
  }>;
  stat: {
    total_days: number;
    current_consecutive_days: number;
    max_consecutive_days: number;
    last_checkin_date: string | null;
  };
}

export const checkinApi = {
  /**
   * 每日签到
   */
  checkin: (): Promise<{ code: number; message: string; data: any }> => {
    return apiClient.post('/v1/checkin');
  },

  /**
   * 获取签到统计
   */
  getStat: (): Promise<{ code: number; message: string; data: CheckinStat }> => {
    return apiClient.get('/v1/checkin/stat');
  },

  /**
   * 获取签到日历
   */
  getCalendar: (year: number, month: number): Promise<{ code: number; message: string; data: CheckinCalendar }> => {
    return apiClient.get('/v1/checkin/calendar', { params: { year, month } });
  },

  /**
   * 补签
   */
  makeup: (date: string): Promise<{ code: number; message: string; data: any }> => {
    return apiClient.post('/v1/checkin/makeup', { date });
  },
};

