<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_in_at'             => ['required', 'date_format:H:i'],
            'clock_out_at'            => ['required', 'date_format:H:i'],
            'requested_note'          => ['required', 'string', 'max:255'],
            // 休憩時間は開始と終了片方のみ入力の場合はエラー
            'breaks.*.break_start_at' => ['nullable', 'date_format:H:i',  'required_with:breaks.*.break_end_at'],
            'breaks.*.break_end_at'   => ['nullable', 'date_format:H:i', 'required_with:breaks.*.break_start_at'],
        ];
    }

    public function messages()
    {
        return [
            'clock_in_at.required'                  => '出勤時間を入力してください',
            'clock_in_at.date_format'               => '時間は00:00の形で入力してください',
            'clock_out_at.required'                 => '退勤時間を入力してください',
            'clock_out_at.date_format'              => '時間は00:00の形で入力してください',
            'breaks.*.break_start_at.date_format'   => '時間は00:00の形で入力してください',
            'breaks.*.break_end_at.date_format'     => '時間は00:00の形で入力してください',
            'breaks.*.break_start_at.required_with' => '休憩時間を入力してください',
            'breaks.*.break_end_at.required_with'   => '休憩時間を入力してください',
            'requested_note.required'               => '備考を記入してください',
            'requested_note.max'                    => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator){
            // メッセージを定義
            $clockMessage = '出勤時間もしくは退勤時間が不適切な値です';
            $breakStartMessage = '休憩時間が不適切な値です';
            $breakEndMessage = '休憩時間もしくは退勤時間が不適切な値です';

            $clockIn = $this->input('clock_in_at');
            $clockOut = $this->input('clock_out_at');
            $breaks = $this->input('breaks', []);

            // 出勤・退勤チェック
            if ($clockIn && $clockOut) {
                if ($clockIn >= $clockOut) {
                    $validator->errors()->add('clock_in_at', $clockMessage);
                }
            }

            // 休憩チェック
            foreach ($breaks as $index => $break) {
                $breakStart = $break['break_start_at'] ?? null;
                $breakEnd = $break['break_end_at'] ?? null;

                // 休憩開始と終了が両方ある場合、前後関係を見る
                if ($breakStart && $breakEnd) {
                    if ($breakStart >= $breakEnd) {
                        $validator->errors()->add("breaks.$index.break_start_at", $breakStartMessage);
                    }
                }

                if ($breakStart) {
                    if ($breakStart < $clockIn || $breakStart > $clockOut) {
                        $validator->errors()->add("breaks.$index.break_start_at", $breakStartMessage);
                    }
                }

                if ($breakEnd) {
                    if ($breakEnd > $clockOut) {
                        $validator->errors()->add("breaks.$index.break_end_at", $breakEndMessage);
                    }
                }
            }
        });
    }
}