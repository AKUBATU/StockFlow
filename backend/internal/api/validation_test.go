package api

import (
	"encoding/json"
	"testing"
)

func TestValidateOrder(t *testing.T) {
	cases := []struct {
		name, body string
		valid      bool
	}{
		{"valid", `{"customer":"Acme","items":[{"sku":"A","qty":2},{"sku":"B","qty":1}]}`, true},
		{"blank customer", `{"customer":" ","items":[{"sku":"A","qty":1}]}`, false},
		{"empty items", `{"customer":"Acme","items":[]}`, false},
		{"duplicate SKU", `{"customer":"Acme","items":[{"sku":"A","qty":1},{"sku":"A","qty":2}]}`, false},
		{"zero quantity", `{"customer":"Acme","items":[{"sku":"A","qty":0}]}`, false},
		{"negative quantity", `{"customer":"Acme","items":[{"sku":"A","qty":-1}]}`, false},
		{"overflow quantity", `{"customer":"Acme","items":[{"sku":"A","qty":2147483648}]}`, false},
		{"blank SKU", `{"customer":"Acme","items":[{"sku":"","qty":1}]}`, false},
		{"untrimmed SKU", `{"customer":"Acme","items":[{"sku":" A ","qty":1}]}`, false},
	}
	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			var in orderInput
			if err := json.Unmarshal([]byte(tc.body), &in); err != nil {
				t.Fatal(err)
			}
			err := validateOrder(in)
			if (err == nil) != tc.valid {
				t.Fatalf("validateOrder() = %v, valid wanted %v", err, tc.valid)
			}
		})
	}
}
