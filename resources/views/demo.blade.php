<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Report Generator - Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans p-8">

    <div class="max-w-6xl mx-auto space-y-8">
        
        <header class="bg-white rounded-lg shadow p-6">
            <h1 class="text-3xl font-bold text-indigo-600">Dynamic Report Generator Demo</h1>
            <p class="text-gray-600 mt-2">Interactive presentation of Virtual Attributes and Fluent Report Builder.</p>
        </header>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Virtual Attribute Builder Card -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition duration-300 overflow-hidden flex flex-col h-full border border-gray-100">
                <div class="bg-indigo-600 p-6 flex justify-center">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                </div>
                <div class="p-8 flex-grow flex flex-col justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">Virtual Attribute Playground</h2>
                        <p class="text-gray-600 mb-6 leading-relaxed">Interactively define and register powerful SQL subqueries. Tie custom logic directly to your models without writing a single line of PHP.</p>
                    </div>
                    <a href="{{ route('va_builder.index') }}" class="block text-center w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition transform hover:-translate-y-1">
                        Open VA Builder &rarr;
                    </a>
                </div>
            </div>

            <!-- Report Builder Card -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition duration-300 overflow-hidden flex flex-col h-full border border-gray-100">
                <div class="bg-emerald-600 p-6 flex justify-center">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div class="p-8 flex-grow flex flex-col justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">Dynamic Report Builder</h2>
                        <p class="text-gray-600 mb-6 leading-relaxed">Visually orchestrate queries against the dynamic report generator engine. Select models, apply grouping, and use your newly built Virtual Attributes seamlessly.</p>
                    </div>
                    <a href="{{ route('builder.index') }}" class="block text-center w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-lg transition transform hover:-translate-y-1">
                        Open Report Builder &rarr;
                    </a>
                </div>
            </div>

    </div>

</body>
</html>
