@extends('frontend.layouts.app')

@section('title', 'Messages')

@section('content')
    <div class="container py-4" style="height: calc(100vh - 250px); min-height: 500px;">
        <h1 class="h4 mb-3">Messages</h1>

        <div style="height: calc(100% - 48px);">
            @livewire('frontend.chat.chat-box', ['conversation' => $conversation], key('chat-page-box'))
        </div>
    </div>
@endsection
